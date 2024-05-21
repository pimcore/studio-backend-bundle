<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\ActionSubmitter;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\WorkflowActionNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\WorkflowActionSubmissionException;
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
    )
    {
    }

    public function submit(
        ElementInterface $element,
        WorkflowInterface $workflow,
        SubmitAction $parameters
    ): ActionSubmissionResponse
    {
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