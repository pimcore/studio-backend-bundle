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
use Pimcore\Bundle\StudioBackendBundle\Workflow\Repository\WorkflowElementsRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;
use Pimcore\Workflow\Manager;
use function array_filter;
use function array_keys;
use function array_map;
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
    ) {
    }

    public function applyVisibleStates(iterable $elements, string $elementType): void
    {
        $elementsById = [];
        foreach ($elements as $element) {
            if ($element instanceof WorkflowStatesAwareInterface && !$this->isAssetFolder($element, $elementType)) {
                $elementsById[$element->getId()] = $element;
            }
        }

        if ($elementsById === []) {
            return;
        }

        $statesById = [];
        $rows = $this->elementsRepository->fetchPlacesByElementIds($elementType, array_keys($elementsById));
        foreach ($rows as $row) {
            $elementId = (int) $row['cid'];
            if (!isset($elementsById[$elementId])) {
                continue;
            }

            foreach ($this->getVisibleStates((string) $row['workflow'], (string) $row['place']) as $state) {
                $statesById[$elementId][] = $state;
            }
        }

        foreach ($statesById as $elementId => $states) {
            $elementsById[$elementId]->setWorkflowStates($states);
        }
    }

    // the editor never shows workflows on asset folders, although their places are stored
    private function isAssetFolder(WorkflowStatesAwareInterface $element, string $elementType): bool
    {
        return $elementType === ElementTypes::TYPE_ASSET && $element->getType() === ElementTypes::TYPE_FOLDER;
    }

    /**
     * @return WorkflowStatus[]
     */
    private function getVisibleStates(string $workflowName, string $storedPlaces): array
    {
        $places = array_filter(array_map('trim', explode(',', $storedPlaces)));
        $states = [];

        // iterating the config keeps the order of the workflow definition, like the detail view
        foreach ($this->workflowManager->getPlaceConfigsByWorkflowName($workflowName) as $placeConfig) {
            if (!$placeConfig->isVisibleInHeader() || !in_array($placeConfig->getPlace(), $places, true)) {
                continue;
            }

            $states[] = new WorkflowStatus(
                $placeConfig->getColor(),
                $placeConfig->getColorInverted(),
                $placeConfig->getPlace(),
                $placeConfig->getLabel(),
                null,
                true,
            );
        }

        return $states;
    }
}
