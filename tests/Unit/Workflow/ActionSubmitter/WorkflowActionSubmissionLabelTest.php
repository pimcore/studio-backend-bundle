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
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\WorkflowActionSubmissionException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\WorkflowActionTypes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\ActionSubmitter\GlobalActionSubmitter;
use Pimcore\Bundle\StudioBackendBundle\Workflow\ActionSubmitter\TransitionActionSubmitter;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Workflow\GlobalAction;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Transition;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\Transition as SymfonyTransition;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Regression test for platform-version#784: when a workflow action fails, the
 * submission error names the action by its label ("Ready for processing"), not
 * by its technical name (ready_for_processing).
 *
 * @internal
 */
final class WorkflowActionSubmissionLabelTest extends Unit
{
    private const string WORKFLOW_NAME = 'studio_label_test';

    private const string ACTION_NAME = 'ready_for_processing';

    private const string ACTION_LABEL = 'Ready for processing';

    private const string FAILURE = 'Field X is missing.';

    public function testATransitionFailureNamesTheTransitionByItsLabel(): void
    {
        $transition = new Transition(self::ACTION_NAME, 'draft', 'done', ['label' => self::ACTION_LABEL]);

        self::assertSame(
            sprintf('Submitting action %s failed: %s', self::ACTION_LABEL, self::FAILURE),
            $this->submitFailingTransition($transition)
        );
    }

    public function testATransitionWithoutAPimcoreLabelFallsBackToItsName(): void
    {
        $transition = new SymfonyTransition(self::ACTION_NAME, 'draft', 'done');

        self::assertSame(
            sprintf('Submitting action %s failed: %s', self::ACTION_NAME, self::FAILURE),
            $this->submitFailingTransition($transition)
        );
    }

    public function testATransitionMissingFromTheDefinitionFallsBackToItsName(): void
    {
        $otherTransition = new Transition('other_transition', 'draft', 'done', ['label' => 'Other transition']);

        self::assertSame(
            sprintf('Submitting action %s failed: %s', self::ACTION_NAME, self::FAILURE),
            $this->submitFailingTransition($otherTransition)
        );
    }

    public function testAGlobalActionFailureNamesTheActionByItsLabel(): void
    {
        $globalAction = $this->createMock(GlobalAction::class);
        $globalAction->method('isGuardValid')->willReturn(true);
        $globalAction->method('getLabel')->willReturn(self::ACTION_LABEL);

        $manager = $this->createMock(Manager::class);
        $manager->method('getGlobalAction')->willReturn($globalAction);
        $manager->method('applyGlobalAction')->willThrowException(new Exception(self::FAILURE));

        $submitter = new GlobalActionSubmitter($manager);

        try {
            $submitter->submit(
                $this->createMock(Concrete::class),
                $this->workflow(new Transition(self::ACTION_NAME, 'draft', 'done')),
                $this->parameters(WorkflowActionTypes::GLOBAL_ACTION)
            );
            self::fail('The failing global action did not throw');
        } catch (WorkflowActionSubmissionException $exception) {
            self::assertSame(
                sprintf('Submitting action %s failed: %s', self::ACTION_LABEL, self::FAILURE),
                $exception->getMessage()
            );
        }
    }

    private function submitFailingTransition(SymfonyTransition $transition): string
    {
        $manager = $this->createMock(Manager::class);
        $manager->method('applyWithAdditionalData')->willThrowException(new Exception(self::FAILURE));

        $submitter = new TransitionActionSubmitter($manager);

        try {
            $submitter->submit(
                $this->createMock(Concrete::class),
                $this->workflow($transition),
                $this->parameters(WorkflowActionTypes::TRANSITION_ACTION)
            );
        } catch (WorkflowActionSubmissionException $exception) {
            return $exception->getMessage();
        }

        self::fail('The failing transition did not throw');
    }

    private function workflow(SymfonyTransition $transition): WorkflowInterface
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('can')->willReturn(true);
        $workflow->method('getDefinition')->willReturn(new Definition(['draft', 'done'], [$transition]));

        return $workflow;
    }

    private function parameters(string $actionType): SubmitAction
    {
        return new SubmitAction(
            $actionType,
            1,
            ElementTypes::TYPE_DATA_OBJECT,
            self::WORKFLOW_NAME,
            self::ACTION_NAME
        );
    }
}
