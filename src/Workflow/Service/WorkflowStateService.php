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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Service;

use Pimcore\Bundle\StudioBackendBundle\Response\WorkflowStatesAwareInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\WorkflowStatusHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Repository\WorkflowElementsRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;
use Pimcore\Workflow\Manager;
use function array_keys;
use function explode;
use function in_array;

/**
 * @internal
 */
final readonly class WorkflowStateService implements WorkflowStateServiceInterface
{
    public function __construct(
        private Manager $workflowManager,
        private WorkflowElementsRepositoryInterface $elementsRepository,
        private WorkflowStatusHydratorInterface $workflowStatusHydrator,
    ) {
    }

    public function applyVisibleStates(iterable $elements, string $elementType): void
    {
        $elementsById = [];
        foreach ($elements as $element) {
            if (!$element instanceof WorkflowStatesAwareInterface) {
                continue;
            }

            if ($this->showsWorkflowStates($element, $elementType)) {
                $elementsById[$element->getId()] = $element;
            }
        }

        if ($elementsById === []) {
            return;
        }

        $placesByElement = [];
        foreach ($this->elementsRepository->fetchPlacesByElementIds($elementType, array_keys($elementsById)) as $row) {
            $placesByElement[(int) $row['cid']][$row['workflow']] = (string) $row['place'];
        }

        $workflowNames = $this->workflowManager->getAllWorkflows();
        foreach ($elementsById as $elementId => $element) {
            $element->setWorkflowStates($this->getVisibleStates($workflowNames, $placesByElement[$elementId] ?? []));
        }
    }

    // like the editor: workflow details need the view permission, and asset folders never show workflows
    private function showsWorkflowStates(WorkflowStatesAwareInterface $element, string $elementType): bool
    {
        if (!$element->getPermissions()->isView()) {
            return false;
        }

        return $elementType !== ElementTypes::TYPE_ASSET || $element->getType() !== ElementTypes::TYPE_FOLDER;
    }

    /**
     * @param string[] $workflowNames
     * @param array<string, string> $placesByWorkflow
     *
     * @return WorkflowStatus[]
     */
    private function getVisibleStates(array $workflowNames, array $placesByWorkflow): array
    {
        $states = [];
        // workflows by priority and places in configuration order, like the detail view
        foreach ($workflowNames as $workflowName) {
            if (!isset($placesByWorkflow[$workflowName])) {
                continue;
            }

            $places = explode(',', $placesByWorkflow[$workflowName]);
            foreach ($this->workflowManager->getPlaceConfigsByWorkflowName($workflowName) as $placeConfig) {
                if ($placeConfig->isVisibleInHeader() && in_array($placeConfig->getPlace(), $places, true)) {
                    $states[] = $this->workflowStatusHydrator->hydrate($placeConfig);
                }
            }
        }

        return $states;
    }
}
