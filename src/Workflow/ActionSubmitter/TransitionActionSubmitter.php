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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\WorkflowActionNotAllowedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\WorkflowActionSubmissionException;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Response\ActionSubmissionResponse;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\Manager;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
final readonly class TransitionActionSubmitter implements TransitionActionSubmitterInterface
{
    public function __construct(
        private Manager $workflowManager,
    ) {
    }

    public function submit(
        ElementInterface $element,
        WorkflowInterface $workflow,
        SubmitAction $parameters,
    ): ActionSubmissionResponse {
        $element = $this->validateElementType($element);
        $transitionName = $parameters->getTransition();
        if (!$workflow->can($element, $transitionName)) {
            throw new WorkflowActionNotAllowedException(
                $transitionName,
                $parameters->getWorkflowName()
            );
        }

        try {
            $this->workflowManager->applyWithAdditionalData(
                $workflow,
                $element,
                $transitionName,
                $parameters->getWorkflowOptions()
            );

            return new ActionSubmissionResponse(
                $parameters->getWorkflowName(),
                $transitionName,
                $parameters->getActionType()
            );
        } catch (Exception $e) {
            throw new WorkflowActionSubmissionException(
                $transitionName,
                $e->getMessage()
            );
        }
    }

    private function validateElementType(ElementInterface $element): Asset|Concrete|PageSnippet
    {
        if (!$element instanceof Asset &&
            !$element instanceof Concrete &&
            !$element instanceof PageSnippet
        ) {
            throw new InvalidElementTypeException($element->getType());
        }

        return $element;
    }
}
