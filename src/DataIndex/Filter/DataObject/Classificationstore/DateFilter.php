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

use Carbon\Carbon;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\DateFilter as GDIDateFilter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DateTimeTrait;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\GetClassificationStoreFilterValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class DateFilter implements FilterInterface
{
    use GetClassificationStoreFilterValueTrait;
    use DateTimeTrait;

    public function __construct(
        private GroupConfigRepositoryInterface $groupConfigRepository,
        private KeyGroupRelationRepositoryInterface $keyGroupRelationRepository
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
            /** @var DataObjectQueryInterface $query */
            $query = $this->applyDateFilter($column, $query);
        }

        return $query;
    }

    private function applyDateFilter(ColumnFilter $column, DataObjectQueryInterface $query): QueryInterface
    {
        $filterValue = $this->getClassificationStoreFilterValue($column->getFilterValue());
        $key = $this->keyGroupRelationRepository->getByKeyId($filterValue->getKeyId());
        $group = $this->groupConfigRepository->getById($filterValue->getGroupId());

        $this->setFilterValue($filterValue->getValue());

        if (isset($this->filterValue['from'], $this->filterValue['to'])) {

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                $this->buildDateFilterModifier($key->getName(), $this->getFromAsCarbon(), $this->getToAsCarbon()),
                $column->getLocale()
            );

            return $query;
        }

        if (isset($this->filterValue['on'])) {
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                $this->buildDateFilterModifier($key->getName(), null, null, $this->getOnAsCarbon()),
                $column->getLocale()
            );
        }

        if (isset($this->filterValue['to'])) {
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                $this->buildDateFilterModifier($key->getName(), null, $this->getToAsCarbon()),
                $column->getLocale()
            );
        }

        if (isset($this->filterValue['from'])) {
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                $this->buildDateFilterModifier($key->getName(), $this->getFromAsCarbon()),
                $column->getLocale()
            );
            $query->filterDatetime($column->getKey(), $this->getFromAsCarbon());
        }

        return $query;
    }

    private function buildDateFilterModifier(
        string $field,
        Carbon $startDate = null,
        Carbon $endDate = null,
        Carbon $onDate = null,
    ): GDIDateFilter {
        return new GDIDateFilter($field, $startDate, $endDate, $onDate);
    }
}
