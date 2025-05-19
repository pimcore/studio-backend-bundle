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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\ActionSubmitter;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\WorkflowActionNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\WorkflowActionSubmissionException;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Response\ActionSubmissionResponse;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\Manager;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
final readonly class GlobalActionSubmitter implements GlobalActionSubmitterInterface
{
    public function __construct(
        private Manager $workflowManager,
    ) {
    }

    public function submit(
        ElementInterface $element,
        WorkflowInterface $workflow,
        SubmitAction $parameters
    ): ActionSubmissionResponse {
        $workflowName = $parameters->getWorkflowName();
        $actionName = $parameters->getTransition();

        $globalAction = $this->workflowManager->getGlobalAction(
            $workflowName,
            $actionName
        );
        if (!$globalAction) {
            throw new WorkflowActionNotFoundException(
                $actionName,
                $workflowName
            );
        }

        try {
            $this->workflowManager->applyGlobalAction(
                $workflow,
                $element,
                $actionName,
                $parameters->getWorkflowOptions(),
                $globalAction->getSaveSubject()
            );

            return new ActionSubmissionResponse(
                $workflowName,
                $actionName,
                $parameters->getActionType()
            );
        } catch (Exception $e) {
            throw new WorkflowActionSubmissionException(
                $actionName,
                $e->getMessage()
            );
        }
    }
}
