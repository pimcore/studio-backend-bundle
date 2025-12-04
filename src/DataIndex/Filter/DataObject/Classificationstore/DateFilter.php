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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\Classificationstore;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DateTimeTrait;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\GetClassificationStoreFilterValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class DateFilter implements FilterInterface
{
    use GetClassificationStoreFilterValueTrait;
    use DateTimeTrait;

    public function __construct(
        private readonly GroupConfigRepositoryInterface $groupConfigRepository,
        private readonly KeyGroupRelationRepositoryInterface $keyGroupRelationRepository
    ) {

    }

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }

        if (!$query instanceof DataObjectQueryInterface) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::CLASSIFICATION_STORE_DATE->value) as $column) {
            $filterValue = $this->getClassificationStoreFilterValue($column->getFilterValue());
            $key = $this->keyGroupRelationRepository->getByKeyGroupId($filterValue->getKeyId(), $filterValue->getGroupId());
            $group = $this->groupConfigRepository->getById($filterValue->getGroupId());
            /** @var DataObjectQueryInterface $query */
            $query = $this->applyClassificationStoreDateFilter($column, $query, $key, $group, $filterValue, true);
        }

        return $query;
    }
}
