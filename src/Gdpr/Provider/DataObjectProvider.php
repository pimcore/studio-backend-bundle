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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DataObjectQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Exporter\ObjectExporterInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataRow;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use function sprintf;

/**
 * @internal
 */
final readonly class DataObjectProvider implements DataProviderInterface
{
    private array $dataObjectConfig;

    public function __construct(
        private DataObjectQueryProviderInterface $query,
        private DataObjectSearchServiceInterface $searchService,
        private ObjectExporterInterface $objectExporter,
        array $gdprConfig = []
    ) {
        $this->dataObjectConfig = $gdprConfig['data_objects'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function findData(FilterParameter $filter): Collection
    {
        $query = $this->query->createDataObjectQuery();

        $query->excludeFolders();

        $idFilter = $filter->getSimpleColumnFilterByType('id');
        if ($idFilter !== null) {
            $query->filterInteger('id', (int)$idFilter->getFilterValue());
        }

        $textFilterTypes = ['firstname', 'lastname', 'email'];
        $searchTerms = [];

        foreach ($textFilterTypes as $filterType) {
            $textFilter = $filter->getSimpleColumnFilterByType($filterType);
            if ($textFilter === null) {
                continue;
            }

            $value = trim((string)$textFilter->getFilterValue());
            if ($value !== '') {
                $searchTerms[] = $value;
            }
        }

        if ($searchTerms !== []) {
            $query->filterMultiMatch(implode(' ', $searchTerms), [], 'cross_fields', 'and');
        }

        $this->applySearchOptions($query, $filter);

        $searchResult = $this->searchService->searchDataObjects($query);

        $items   = $searchResult->getItems();

        $rows = array_map(
            fn ($item) => new GdprDataRow([
                'type' => $item->getType(),
                'id' => $item->getId(),
                'fullPath' => $item->getFullPath(),
                'className' => $item->getClassName(),
                '__gdprIsDeletable' =>
                    $this->dataObjectConfig['classes'][$item->getClassName()]['allowDelete'] ?? false,
            ]),
            $items
        );

        return new Collection(
            totalItems: $searchResult->getTotalItems(),
            items: $rows
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

        if (!$object->isAllowed(ElementPermissions::VIEW_PERMISSION)) {
            throw new ForbiddenException(sprintf('Access Denied for object with id "%d".', $object->getId()));
        }

        $export = [
            'id' => $object->getId(),
            'fullPath' => $object->getFullPath(),
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
}
