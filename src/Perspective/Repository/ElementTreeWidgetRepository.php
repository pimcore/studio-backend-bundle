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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Repository;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\Search\LocateInTreeServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\PathServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\ElementSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\TreeQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\TreeLevelData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Model\WidgetElementData;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\ElementTreeWidgetConfig;
use Pimcore\Model\UserInterface;
use function array_slice;
use function count;

/**
 * @internal
 */
final readonly class ElementTreeWidgetRepository implements ElementTreeWidgetRepositoryInterface
{
    public function __construct(
        private ElementServiceInterface $elementService,
        private ElementSearchServiceInterface $elementSearchService,
        private LocateInTreeServiceInterface $locateInTreeService,
        private PathServiceInterface $pathService,
        private TreeQueryInterface $treeQuery,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgetByElement(
        array $allWidgets,
        string $elementType,
        int $elementId,
        SearchIndexFilterInterface $filterService,
        UserInterface $user
    ): ?WidgetElementData {
        foreach ($allWidgets as $widgetCollection) {
            foreach ($widgetCollection as $widget) {
                if (!$widget instanceof ElementTreeWidgetConfig ||
                    $widget->getElementType() !== $elementType
                ) {
                    continue;
                }

                $element = $this->elementSearchService->findElementInTree(
                    $widget->getElementType(),
                    $elementId,
                    $this->treeQuery->get($widget, $filterService, $user)
                );

                if ($element !== null) {
                    return new WidgetElementData($widget, $element);
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTreeLevelData(
        WidgetElementData $widgetElementData,
        SearchIndexFilterInterface $filterService,
        UserInterface $user
    ): array {
        $widget = $widgetElementData->getWidgetConfig();
        $element = $widgetElementData->getResultItem();
        $treeLevelData = [];
        $parents = $this->getParentElements($widget, $element, $user);
        if (empty($parents)) {
            return [new TreeLevelData(parentId: 1, elementId: $element->getId(), pageNumber: 1)];
        }

        $parentCount = count($parents);
        for ($index = 0; $index < $parentCount - 1; $index++) {
            $query = $this->treeQuery->get($widget, $filterService, $user, $parents[$index]);
            $treeLevelData[] = $this->setTreeLevelData($parents[$index], $parents[$index + 1], $query->getSearch());
        }

        $lastParentId = $parents[$parentCount - 1];
        $query = $this->treeQuery->get($widget, $filterService, $user, $lastParentId);
        $treeLevelData[] = $this->setTreeLevelData($lastParentId, $element->getId(), $query->getSearch());

        return $treeLevelData;
    }

    /**
     * @throws ForbiddenException|NotFoundException
     *
     */
    private function getParentElements(
        ElementTreeWidgetConfig $widget,
        ElementSearchResultItemInterface $element,
        UserInterface $user
    ): array {
        $levels = $this->pathService->getAllParentPaths([$element->getFullPath()]);
        $levels = $this->filterParentPaths($levels, $widget->getRootFolder());

        $parents = [];
        foreach ($levels as $level) {
            $parents[] = $this->elementService->getAllowedElementByPath(
                $widget->getElementType(),
                $level,
                $user
            )->getId();
        }

        return $parents;
    }

    private function filterParentPaths(array $parentPaths, string $rootPath): array
    {
        $index = array_search($rootPath, $parentPaths, true);

        if ($index === false) {
            return [];
        }

        return array_values(array_slice($parentPaths, $index));
    }

    /**
     * @throws NotFoundException
     */
    private function setTreeLevelData(int $parentId, int $elementId, SearchInterface $search): TreeLevelData
    {
        $page = $this->locateInTreeService->getPageNumber($search, $elementId);
        if ($page === null) {
            throw new NotFoundException('Element', $elementId);
        }

        return new TreeLevelData(
            parentId: $parentId,
            elementId: $elementId,
            pageNumber: $page,
        );
    }
}
