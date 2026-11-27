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
use Pimcore\Model\Element\ValidationException;
use Pimcore\Workflow\GlobalAction;
use Pimcore\Workflow\Manager;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * A failed workflow action keeps what failed as its cause, so a caller can tell a save that
 * validation refused from any other error.
 *
 * @internal
 */
final class FailedActionCauseTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testFailedTransitionKeepsItsCause(): void
    {
        $cause = new ValidationException('Empty mandatory field [ name ]');
        $manager = $this->makeEmpty(Manager::class, [
            'applyWithAdditionalData' => static function () use ($cause): never {
                throw $cause;
            },
        ]);

        $this->assertSubmissionKeepsCause(
            new TransitionActionSubmitter($manager),
            WorkflowActionTypes::TRANSITION_ACTION,
            $cause
        );
    }

    /**
     * @throws Exception
     */
    public function testFailedGlobalActionKeepsItsCause(): void
    {
        $cause = new ValidationException('Empty mandatory field [ name ]');
        $manager = $this->makeEmpty(Manager::class, [
            'getGlobalAction' => $this->makeEmpty(GlobalAction::class, ['isGuardValid' => true]),
            'applyGlobalAction' => static function () use ($cause): never {
                throw $cause;
            },
        ]);

        $this->assertSubmissionKeepsCause(
            new GlobalActionSubmitter($manager),
            WorkflowActionTypes::GLOBAL_ACTION,
            $cause
        );
    }

    /**
     * @throws Exception
     */
    private function assertSubmissionKeepsCause(
        TransitionActionSubmitter|GlobalActionSubmitter $submitter,
        string $actionType,
        ValidationException $cause
    ): void {
        try {
            $submitter->submit(
                $this->makeEmpty(Concrete::class),
                $this->makeEmpty(WorkflowInterface::class, ['can' => true]),
                new SubmitAction($actionType, 1, ElementTypes::TYPE_DATA_OBJECT, 'review', 'publish')
            );
            $this->fail('Expected WorkflowActionSubmissionException');
        } catch (WorkflowActionSubmissionException $exception) {
            $this->assertSame(
                'Submitting action publish failed: Empty mandatory field [ name ]',
                $exception->getMessage()
            );
            $this->assertSame($cause, $exception->getPrevious());
        }
    }
}
