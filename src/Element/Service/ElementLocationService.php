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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use JsonException;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\Search\LocateInTreeServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\PathServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\ElementSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\DataObjectParameters;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ElementParameters as IndexElementParameters;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResponse\ElementLocateEvent;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\LocationData;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\TreeLevelData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Model\WidgetElementData;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\ElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\PerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_slice;
use function count;
use function sprintf;

/**
 * @internal
 */
final readonly class ElementLocationService implements ElementLocationServiceInterface
{
    public function __construct(
        private DataObjectServiceInterface $dataObjectService,
        private ElementSearchServiceInterface $elementSearchService,
        private ElementServiceInterface $elementService,
        private EventDispatcherInterface $eventDispatcher,
        private FilterServiceProviderInterface $filterServiceProvider,
        private LocateInTreeServiceInterface $locateInTreeService,
        private PathServiceInterface $pathService,
        private PerspectiveServiceInterface $perspectiveService,
        private SecurityServiceInterface $securityService,
    ) {
    }

    /**
     * @throws ForbiddenException|InvalidArgumentException|InvalidQueryTypeException|InvalidFilterTypeException
     * @throws NotFoundException|NotWriteableException|UserNotFoundException
     */
    public function getElementLocation(string $elementType, int $elementId, string $perspectiveId): LocationData
    {
        $user = $this->securityService->getCurrentUser();
        $this->elementService->getAllowedElementById($elementType, $elementId, $user);

        $perspective = $this->perspectiveService->getConfigData($perspectiveId);
        $filterService = $this->filterServiceProvider->create(SearchIndexFilterInterface::SERVICE_TYPE);

        $widgetElementData = $this->getWidgetAndElement(
            [$perspective->getWidgetsLeft(), $perspective->getWidgetsRight(), $perspective->getWidgetsBottom()],
            $elementType,
            $elementId,
            $filterService,
            $user
        );

        if ($widgetElementData === null) {
            throw new NotFoundException(
                'Element',
                sprintf('(id: %d) (type: %s)', $elementId, $elementType),
                'Id and Type'
            );
        }

        $widget = $widgetElementData->getWidgetConfig();
        $locationData = new LocationData(
            $widget->getId(),
            $this->getTreeLevelData($widget, $filterService, $widgetElementData->getResultItem(), $user)
        );
        $this->eventDispatcher->dispatch(
            new ElementLocateEvent($locationData),
            ElementLocateEvent::EVENT_NAME
        );

        return $locationData;
    }

    /**
     * @throws ForbiddenException|InvalidArgumentException|InvalidQueryTypeException
     * @throws InvalidFilterTypeException|NotFoundException
     */
    private function getWidgetAndElement(
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
                    $this->getTreeQuery($widget, $filterService, $this->getFilterParameters($widget), $user)
                );

                if ($element !== null) {
                    return new WidgetElementData($widget, $element);
                }
            }
        }

        return null;
    }

    /**
     * @throws ForbiddenException|InvalidArgumentException|InvalidFilterTypeException|InvalidQueryTypeException
     * @throws NotFoundException
     *
     * @return TreeLevelData[]
     */
    private function getTreeLevelData(
        ElementTreeWidgetConfig $widget,
        SearchIndexFilterInterface $filterService,
        ElementSearchResultItemInterface $element,
        UserInterface $user
    ): array {
        $treeLevelData = [];
        $parents = $this->getParentElements($widget, $element, $user);
        if (empty($parents)) {
            return [new TreeLevelData(parentId: 1, elementId: $element->getId(), pageNumber: 1)];
        }

        $parentCount = count($parents);
        for ($index = 0; $index < $parentCount - 1; $index++) {
            $query = $this->getTreeQuery(
                $widget,
                $filterService,
                $this->getFilterParameters($widget, $parents[$index]),
                $user
            );

            $treeLevelData[] = $this->setTreeLevelData(
                $parents[$index],
                $parents[$index + 1],
                $query->getSearch()
            );
        }

        $lastParentId = $parents[$parentCount - 1];
        $query = $this->getTreeQuery(
            $widget,
            $filterService,
            $this->getFilterParameters($widget, $lastParentId),
            $user
        );
        $treeLevelData[] = $this->setTreeLevelData($lastParentId, $element->getId(), $query->getSearch());

        return $treeLevelData;
    }

    /**
     * @throws InvalidQueryTypeException|InvalidFilterTypeException|NotFoundException
     */
    private function getTreeQuery(
        ElementTreeWidgetConfig $widget,
        SearchIndexFilterInterface $filterService,
        CollectionParametersInterface $filterParameters,
        UserInterface $user
    ): QueryInterface {
        $type = $widget->getElementType();
        $query = $filterService->applyFilters($filterParameters, $type);
        if ($type === ElementTypes::TYPE_DATA_OBJECT) {
            $this->handleTreeSorting($type, $widget->getRootFolder(), $query, $user);

            return $query;
        }

        $query->orderByPath('asc');

        return $query;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getFilterParameters(
        ElementTreeWidgetConfig $widget,
        ?int $parentId = null
    ): CollectionParametersInterface {
        $includeAllChildren = true;
        $rootPath = $widget->getRootFolder();
        if ($parentId !== null) {
            $includeAllChildren = false;
            $rootPath = null;
        }

        if ($widget->getElementType() === ElementTypes::TYPE_OBJECT) {
            try {
                return new DataObjectParameters(
                    parentId: $parentId,
                    pqlQuery: $widget->getPql(),
                    path: $rootPath,
                    pathIncludeDescendants: $includeAllChildren,
                    classIds: json_encode($widget->getClasses(), JSON_THROW_ON_ERROR),
                );
            } catch (JsonException $e) {
                throw new InvalidArgumentException(
                    sprintf('Could not create parameters for widget with ID: %s', $widget->getId()),
                    previous: $e
                );
            }
        }

        return new IndexElementParameters(
            parentId: $parentId,
            pqlQuery: $widget->getPql(),
            path: $rootPath,
            pathIncludeDescendants: $includeAllChildren,
        );
    }

    /**
     * @throws NotFoundException
     */
    private function handleTreeSorting(
        string $type,
        string $rootPath,
        QueryInterface $query,
        UserInterface $user
    ): void {
        $parent = $this->elementService->getAllowedElementByPath($type, $rootPath, $user);

        if (!$parent instanceof DataObject) {
            throw new NotFoundException(
                'Data object',
                sprintf('(path: %s) (type: %s)', $rootPath, $type),
                'Path and Type'
            );
        }

        $this->dataObjectService->setTreeSorting($parent, $query);
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
