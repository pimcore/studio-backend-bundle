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

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class SelectFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }


        foreach ($parameters->getColumnFilterByType(ColumnType::SYSTEM_SELECT->value) as $column) {
            $query = $this->applySelectFilter($column, $query);
        }

        return $query;
    }

    private function applySelectFilter(ColumnFilter $column, QueryInterface $query): QueryInterface
    {
        $fiterValue = $column->getFilterValue();

        if (!is_array($fiterValue)) {
            throw new InvalidArgumentException('Value for select filter must ba an array');
        }


        return $query->filterMultiSelect($column->getKey(), $column->getFilterValue());
    }
}
