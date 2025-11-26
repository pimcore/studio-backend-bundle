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

use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\SearchTerms;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataColumn;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataRow;
use Pimcore\Model\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DataObjectQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprSearchOptions;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;


/**
 * @internal
 */
final readonly class DataObjectProvider implements DataProviderInterface
{
    
    public function __construct(
        private DataObjectQueryProviderInterface $query,
        private DataObjectSearchServiceInterface $searchService,
    ) {}


    /**
     * {@inheritdoc}
    */
    public function findData(SearchTerms $terms, GdprSearchOptions $options): array
    {
        $query = $this->query->createDataObjectQuery();

        $searchTerm = $this->buildSearchTerm($terms);

        $query->filterFullText($searchTerm);

        $this->applySearchOptions($query, $options);

        $searchResult = $this->searchService->searchDataObjects($query);

        $columns = $this->getAvailableColumns();

        $items   = $searchResult->getItems();

        return array_map(
            fn($item) => new GdprDataRow([
                'id' => $item->getId(),
                'key' => $item->getKey(),
                'path' => $item->getPath(),
                'className' => $item->getClassName(),
                'fullPath' => $item->getFullPath(),
                'parentId' => $item->getParentId(),
                'type' => $item->getType(),
                'published' => $item->isPublished(),
                'creationDate' => date('Y-m-d H:i:s', $item->getCreationDate()),
                'modificationDate' => date('Y-m-d H:i:s', $item->getModificationDate()),
            ], $columns),
            $items
        );
    }


    private function applySearchOptions(QueryInterface $query, GdprSearchOptions $options): void
    {
        // Pagination
        $query->setPage($options->page);
        $query->setPageSize($options->pageSize);

        // Sorting
        if ($options->sortFilter && isset($options->sortFilter['key'], $options->sortFilter['direction'])) {
            $directionEnum = SortDirection::tryFrom($options->sortFilter['direction']);
            if ($directionEnum) {
                $query->orderByField($options->sortFilter['key'], $directionEnum);
            }
        }
    }




    private function buildSearchTerm(SearchTerms $terms): string
    {
        return implode(' ', array_filter([$terms->id, $terms->firstname, $terms->lastname, $terms->email]));
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

        return [
            'id' => $object->getId(),
            'key' => $object->getKey(),
            'path' => $object->getPath(),
            'className' => $object->getClassName(),
            'fullPath' => $object->getFullPath(),
            'parentId' => $object->getParentId(),
            'type' => $object->getType(),
            'published' => $object->isPublished(),
            'creationDate' => date('Y-m-d H:i:s', $object->getCreationDate()),
            'modificationDate' => date('Y-m-d H:i:s', $object->getModificationDate()),
        ];
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
                new GdprDataColumn('id', 'ID'),
                new GdprDataColumn('key', 'Key'),
                new GdprDataColumn('fullPath', 'Full Path'),
                new GdprDataColumn('path', 'Path'),
                new GdprDataColumn('className', 'Class Name'),
                new GdprDataColumn('parentId', 'Parent ID'),
                new GdprDataColumn('type', 'Type'),
                new GdprDataColumn('published', 'Published'),
                new GdprDataColumn('creationDate', 'Created At'),
                new GdprDataColumn('modificationDate', 'Updated At'),
            ];
    }
}
