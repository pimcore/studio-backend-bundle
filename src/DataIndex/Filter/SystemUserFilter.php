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
use function is_array;

/**
 * Filters the "user" system columns (userModification / userOwner) by user id. The client sends the
 * selected user ids as an array; the column key equals the index field, so the ids are matched
 * directly against it. Named SystemUserFilter to avoid a clash with the unrelated UserFilter, which
 * sets the permission/context user on the query.
 *
 * @internal
 */
final class SystemUserFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::SYSTEM_USER->value) as $column) {
            $query = $this->applyUserFilter($column, $query);
        }

        return $query;
    }

    private function applyUserFilter(ColumnFilter $column, QueryInterface $query): QueryInterface
    {
        $filterValue = $column->getFilterValue();

        if (!is_array($filterValue)) {
            throw new InvalidArgumentException('Value for user filter must be an array');
        }

        return $query->filterMultiSelect($column->getKey(), $filterValue);
    }
}
