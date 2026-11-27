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
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Exception\Api\FailureCauseProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\WorkflowActionTypes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\ActionSubmitter\GlobalActionSubmitter;
use Pimcore\Bundle\StudioBackendBundle\Workflow\ActionSubmitter\TransitionActionSubmitter;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Workflow\GlobalAction;
use Pimcore\Workflow\Manager;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * A workflow action that validation refused keeps the refusal as its cause, so a caller can tell it from any
 * other error.
 *
 * @internal
 */
final class FailedActionCauseTest extends Unit
{
    use FailureCauseProviderTrait;

    /**
     * @dataProvider failureCauses
     *
     * @throws Exception
     */
    public function testFailedTransitionKeepsOnlyAValidationCause(Exception $cause, bool $kept): void
    {
        $manager = $this->makeEmpty(Manager::class, [
            'applyWithAdditionalData' => static function () use ($cause): never {
                throw $cause;
            },
        ]);

        $this->assertSubmissionCause(
            new TransitionActionSubmitter($manager),
            WorkflowActionTypes::TRANSITION_ACTION,
            $cause,
            $kept
        );
    }

    /**
     * @dataProvider failureCauses
     *
     * @throws Exception
     */
    public function testFailedGlobalActionKeepsOnlyAValidationCause(Exception $cause, bool $kept): void
    {
        $manager = $this->makeEmpty(Manager::class, [
            'getGlobalAction' => $this->makeEmpty(GlobalAction::class, ['isGuardValid' => true]),
            'applyGlobalAction' => static function () use ($cause): never {
                throw $cause;
            },
        ]);

        $this->assertSubmissionCause(
            new GlobalActionSubmitter($manager),
            WorkflowActionTypes::GLOBAL_ACTION,
            $cause,
            $kept
        );
    }

    /**
     * @throws Exception
     */
    private function assertSubmissionCause(
        TransitionActionSubmitter|GlobalActionSubmitter $submitter,
        string $actionType,
        Exception $cause,
        bool $kept
    ): void {
        try {
            $submitter->submit(
                $this->makeEmpty(Concrete::class),
                $this->makeEmpty(WorkflowInterface::class, ['can' => true]),
                new SubmitAction($actionType, 1, ElementTypes::TYPE_DATA_OBJECT, 'review', 'publish')
            );
            $this->fail('Expected WorkflowActionSubmissionException');
        } catch (WorkflowActionSubmissionException $exception) {
            $this->assertSame('Submitting action publish failed: ' . $cause->getMessage(), $exception->getMessage());
            $this->assertSame($kept ? $cause : null, $exception->getPrevious());
        }
    }
}
