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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Query;

use JsonException;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\DataObjectParameters;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\ElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Setting\Service\SettingsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject;
use Pimcore\Model\UserInterface;
use function sprintf;

final readonly class TreeQuery implements TreeQueryInterface
{
    public function __construct(
        private SettingsServiceInterface $settingsService,
        private DataObjectServiceInterface $dataObjectService,
        private ElementServiceInterface $elementService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function get(
        ElementTreeWidgetConfig $widget,
        SearchIndexFilterInterface $filterService,
        UserInterface $user,
        ?int $parentId = null,
    ): QueryInterface {
        $type = $widget->getElementType();
        $query = $filterService->applyFilters($this->getQueryParameters($widget, $parentId), $type);
        if ($type === ElementTypes::TYPE_DATA_OBJECT) {
            $this->handleTreeSorting($type, $widget->getRootFolder(), $query, $user);

            return $query;
        }

        $query->orderByPath('asc');

        return $query;
    }

    /**
     * @throws InvalidArgumentException|InvalidElementTypeException
     */
    private function getQueryParameters(
        ElementTreeWidgetConfig $widget,
        ?int $parentId = null,
    ): CollectionParametersInterface {
        $includeAllChildren = true;
        $rootPath = $widget->getRootFolder();
        $pageSize = $widget->getPageSize() ?? $this->settingsService->getTreePageSize($widget->getElementType());
        if ($parentId !== null) {
            $includeAllChildren = false;
            $rootPath = null;
        }

        if ($widget->getElementType() === ElementTypes::TYPE_DATA_OBJECT) {
            try {
                return new DataObjectParameters(
                    pageSize: $pageSize,
                    parentId: $parentId,
                    pqlQuery: $widget->getPql(),
                    path: $rootPath,
                    pathIncludeParent: true,
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

        return new ElementParameters(
            pageSize: $pageSize,
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
}
