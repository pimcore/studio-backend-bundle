<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DataObjectQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\SearchTerms;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\Legacy\ObjectExporterInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataColumn;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataRow;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
final readonly class DataObjectProvider implements DataProviderInterface
{
    public function __construct(
        private DataObjectQueryProviderInterface $query,
        private DataObjectSearchServiceInterface $searchService,
        private ObjectExporterInterface $objectExporter
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function findData(SearchTerms $terms, FilterParameter $options): array
    {
        $query = $this->query->createDataObjectQuery();

        $query->excludeFolders();

        if ($terms->getId() !== null) {
            $query->filterInteger('id', $terms->getId());
        }

        $texts = [
            $terms->getFirstname(),
            $terms->getLastname(),
            $terms->getEmail(),
        ];

        foreach ($texts as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $query->filterFullText($value);
            }
        }

        $this->applySearchOptions($query, $options);

        $searchResult = $this->searchService->searchDataObjects($query);

        $columns = $this->getAvailableColumns();

        $items   = $searchResult->getItems();

        return array_map(
            fn ($item) => new GdprDataRow([
                'type' => $item->getType(),
                'id' => $item->getId(),
                'fullPath' => $item->getFullPath(),
                'className' => ($item instanceof Concrete) ? $item->getClassName() : null,
            ], $columns),
            $items
        );
    }

    private function applySearchOptions(QueryInterface $query, FilterParameter $options): void
    {
        $query->setPage($options->getPage());
        $query->setPageSize($options->getPageSize());

        $sortFilter = $options->getSortFilter();

        if ($sortFilter->getKey() && $sortFilter->getDirection()) {
            $directionEnum = strtolower($sortFilter->getDirection()) === SortDirection::DESC->value
                ? SortDirection::DESC
                : SortDirection::ASC;

            $query->orderByField($sortFilter->getKey(), $directionEnum);
        }

    }

    public function getDeleteSwaggerOperationId(): string
    {
        return 'pimcore_studio_api_delete_data_object_grid_configuration'; //is this correct ?
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleItemForDownload(int $id): array
    {
        try {
            $object = DataObject::getById((int)$id);
        } catch (NotFoundException) {
            throw new NotFoundException('Data Object Not Found', $id);
        }

        if (!$object instanceof Concrete) {
            throw new NotFoundException('Requested object is not a Concrete data object', $id);
        }

        $export = [
            'id'            => $object->getId(),
            'fullPath'      => $object->getFullPath(),
        ];

        $properties = $object->getProperties();
        $finalProperties = [];

        foreach ($properties as $property) {
            $finalProperties[] = $property->serialize();
        }

        $export['properties'] = $finalProperties;

        $this->objectExporter->doExportObject($object, $export);

        return $export;
    }

    public function getName(): string
    {
        return 'Data Objects';
    }

    public function getKey(): string
    {
        return 'data_objects';
    }

    public function getSortPriority(): int
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredPermissions(): array
    {
        return [UserPermissions::DATA_OBJECTS->value];
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableColumns(): array
    {
        return [
            new GdprDataColumn('type', 'Type'),
            new GdprDataColumn('id', 'ID'),
            new GdprDataColumn('fullPath', 'Full Path'),
            new GdprDataColumn('className', 'Class Name'),
        ];
    }
}
