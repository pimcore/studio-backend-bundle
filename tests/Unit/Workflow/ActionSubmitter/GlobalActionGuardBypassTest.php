<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Workflow\ActionSubmitter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\WorkflowActionNotAllowedException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\WorkflowActionTypes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\ActionSubmitter\GlobalActionSubmitter;
use Pimcore\Bundle\StudioBackendBundle\Workflow\ActionSubmitter\TransitionActionSubmitter;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;
use Pimcore\Workflow\EventSubscriber\NotesSubscriber;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\Manager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\EventListener\ExpressionLanguage;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;

/**
 * Regression test for platform-version#204 (global-action guard bypass).
 *
 * A workflow *global action* must not be applied when its guard is invalid. Before the fix,
 * {@see GlobalActionSubmitter} applied it regardless, because it never consulted
 * GlobalAction::isGuardValid() (unlike {@see TransitionActionSubmitter}, which gates on
 * Workflow::can()).
 *
 * Uses the real Symfony workflow component, the real Pimcore workflow Manager and the real
 * ExpressionService (only the notes subscriber - irrelevant here - is stubbed). No database
 * is required: an in-memory marking store is used and the global actions are configured with
 * saveSubject=false.
 *
 * @internal
 */
final class GlobalActionGuardBypassTest extends Unit
{
    private const string WORKFLOW_NAME = 'studio_guard_test';

    private const string GUARDED_ACTION = 'force_close';

    private const string ALLOWED_ACTION = 'advance';

    private Manager $manager;

    private Workflow $workflow;

    public function _before(): void
    {
        $dispatcher = new EventDispatcher();

        $builder = new DefinitionBuilder();
        $builder->addPlaces(['open', 'closed', 'done', 'archived']);
        // "finalize" can only fire from "done"
        $builder->addTransition(new Transition('finalize', 'done', 'archived'));

        $this->workflow = new Workflow(
            $builder->build(),
            new MethodMarkingStore(false, 'marking'),
            $dispatcher,
            self::WORKFLOW_NAME
        );

        $expressionService = new ExpressionService(
            new ExpressionLanguage(),
            new TokenStorage(),
            $this->makeEmpty(AuthorizationCheckerInterface::class),
            $this->makeEmpty(AuthenticationTrustResolverInterface::class),
        );

        $this->manager = new Manager(
            new Registry(),
            $this->makeEmpty(NotesSubscriber::class),
            $expressionService,
            $dispatcher
        );

        // Global action guarded by an always-false expression: must never be allowed.
        $this->manager->addGlobalAction(self::WORKFLOW_NAME, self::GUARDED_ACTION, [
            'to' => ['closed'],
            'guard' => 'false',
            'saveSubject' => false,
        ]);

        // Global action with a valid guard: must still be applied.
        $this->manager->addGlobalAction(self::WORKFLOW_NAME, self::ALLOWED_ACTION, [
            'to' => ['done'],
            'guard' => 'true',
            'saveSubject' => false,
        ]);
    }

    public function testGlobalActionIsBlockedWhenGuardInvalid(): void
    {
        $subject = $this->createDataObjectSubject();
        $subject->setMarking(['open' => 1]);

        $globalAction = $this->manager->getGlobalAction(self::WORKFLOW_NAME, self::GUARDED_ACTION);
        self::assertNotNull($globalAction);
        // Precondition: the guard says this action is NOT allowed for this subject.
        self::assertFalse($globalAction->isGuardValid($this->workflow, $subject));

        $this->expectException(WorkflowActionNotAllowedException::class);

        (new GlobalActionSubmitter($this->manager))->submit(
            $subject,
            $this->workflow,
            new SubmitAction(
                WorkflowActionTypes::GLOBAL_ACTION,
                1,
                ElementTypes::TYPE_DATA_OBJECT,
                self::WORKFLOW_NAME,
                self::GUARDED_ACTION,
            )
        );
    }

    public function testGlobalActionIsAppliedWhenGuardValid(): void
    {
        $subject = $this->createDataObjectSubject();
        $subject->setMarking(['open' => 1]);

        $response = (new GlobalActionSubmitter($this->manager))->submit(
            $subject,
            $this->workflow,
            new SubmitAction(
                WorkflowActionTypes::GLOBAL_ACTION,
                1,
                ElementTypes::TYPE_DATA_OBJECT,
                self::WORKFLOW_NAME,
                self::ALLOWED_ACTION,
            )
        );

        self::assertSame(self::ALLOWED_ACTION, $response->getActionName());
        self::assertTrue(
            $this->workflow->getMarking($subject)->has('done'),
            'A global action with a valid guard must still be applied.'
        );
    }

    public function testTransitionIsBlockedWhenNotAllowed(): void
    {
        $subject = $this->createAssetSubject();
        $subject->setMarking(['open' => 1]);

        // Control: the transition path is gated through Workflow::can().
        self::assertFalse($this->workflow->can($subject, 'finalize'));

        $this->expectException(WorkflowActionNotAllowedException::class);

        (new TransitionActionSubmitter($this->manager))->submit(
            $subject,
            $this->workflow,
            new SubmitAction(
                WorkflowActionTypes::TRANSITION_ACTION,
                1,
                ElementTypes::TYPE_ASSET,
                self::WORKFLOW_NAME,
                'finalize',
            )
        );
    }

    /**
     * A DataObject element with an in-memory marking. GlobalActionSubmitter accepts any
     * ElementInterface (it does not validate the concrete element type).
     */
    private function createDataObjectSubject(): object
    {
        return new class extends \Pimcore\Model\DataObject\Folder {
            /** @var array<string, int> */
            private array $marking = [];

            public function getMarking(): array
            {
                return $this->marking;
            }

            /**
             * @param array<string, int>|string $marking
             * @param array<string, mixed> $context
             */
            public function setMarking(array|string $marking, array $context = []): void
            {
                $this->marking = (array) $marking;
            }
        };
    }

    /**
     * An Asset element with an in-memory marking. TransitionActionSubmitter only accepts
     * Asset|Concrete|PageSnippet, so an Asset is used for the control test.
     */
    private function createAssetSubject(): object
    {
        return new class extends \Pimcore\Model\Asset {
            /** @var array<string, int> */
            private array $marking = [];

            public function getMarking(): array
            {
                return $this->marking;
            }

            /**
             * @param array<string, int>|string $marking
             * @param array<string, mixed> $context
             */
            public function setMarking(array|string $marking, array $context = []): void
            {
                $this->marking = (array) $marking;
            }
        };
    }
}
