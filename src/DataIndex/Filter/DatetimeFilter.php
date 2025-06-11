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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\IsAssetFilterTrait;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use function is_array;

/**
 * @internal
 */
final class DatetimeFilter implements FilterInterface
{
    use IsAssetFilterTrait;
    use DateTimeTrait;

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::SYSTEM_DATETIME->value) as $column) {
            $query = $this->applyDatetimeFilter($column, $query);
        }

        return $query;
    }

    private function applyDatetimeFilter(ColumnFilter $column, QueryInterface $query): QueryInterface
    {
        $this->setFilterValue($column);

        $filterValue = $column->getFilterValue();

        if (isset($filterValue['on'])) {
            $query->filterDatetime($column->getKey(), null, null, $this->getOnAsCarbon());
        }

        if (isset($filterValue['to'])) {
            $query->filterDatetime($column->getKey(), null, $this->getToAsCarbon());
        }

        if (isset($filterValue['from'])) {
            $query->filterDatetime($column->getKey(), $this->getFromAsCarbon());
        }

        return $query;
    }
}
