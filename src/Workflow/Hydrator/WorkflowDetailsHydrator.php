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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowDetails;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowActionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowGraphServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Util\Trait\WorkflowLayoutTrait;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\Manager;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
final readonly class WorkflowDetailsHydrator implements WorkflowDetailsHydratorInterface
{
    use WorkflowLayoutTrait;

    public function __construct(
        private AllowedTransitionsHydratorInterface $allowedTransitionsHydrator,
        private GlobalActionsHydratorInterface $globalActionsHydrator,
        private Manager $workflowManager,
        private WorkflowActionServiceInterface $workflowActionService,
        private WorkflowGraphServiceInterface $workflowGraphService,
    ) {
    }

    public function hydrate(ElementInterface $element, WorkflowInterface $workflow): WorkflowDetails
    {
        $statusData = $this->getStatusInfo($workflow, $element);

        return new WorkflowDetails(
            $workflow->getName(),
            $this->getWorkflowLabel($workflow),
            $statusData,
            $this->workflowGraphService->getGraph(
                $element,
                $workflow,
                'svg'
            ),
            $this->getLastLayoutId($statusData),
            $this->allowedTransitionsHydrator->hydrate(
                $workflow->getEnabledTransitions($element),
                $element
            ),
            $this->globalActionsHydrator->hydrate(
                $this->workflowActionService->getGlobalActions($workflow, $element),
                $element
            ),
        );
    }

    private function getWorkflowLabel(WorkflowInterface $workflow): string
    {
        return $this->workflowManager->getWorkflowConfig($workflow->getName())->getLabel();
    }

    private function getStatusInfo(
        WorkflowInterface $workflow,
        ElementInterface $element,
    ): array {
        $marking = $workflow->getMarking($element);
        $statuses = $this->workflowManager->getOrderedPlaceConfigs($workflow, $marking);
        $uniqueStatuses = [];
        $statusInfos = [];

        foreach ($statuses as $status) {
            $uniqueStatuses[$status->getPlace()] = $status;
        }

        foreach ($uniqueStatuses as $status) {
            $layoutId = null;
            if ($element instanceof Concrete) {
                $layoutId = $status->getObjectLayout($workflow, $element);
            }

            $statusInfos[] = new WorkflowStatus(
                $status->getColor(),
                $status->getColorInverted(),
                $status->getPlace(),
                $status->getLabel(),
                $layoutId,
                $status->isVisibleInHeader(),
            );
        }

        return $statusInfos;
    }
}
