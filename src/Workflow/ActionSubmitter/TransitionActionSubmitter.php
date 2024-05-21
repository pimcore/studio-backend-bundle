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
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\WorkflowActionNotAllowedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\WorkflowActionSubmissionException;
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
    )
    {
    }

    public function submit(
        ElementInterface $element,
        WorkflowInterface $workflow,
        SubmitAction $parameters,
    ): ActionSubmissionResponse
    {
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