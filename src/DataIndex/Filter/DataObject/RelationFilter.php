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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

use function is_array;

/**
 * @internal
 */
final class RelationFilter implements FilterInterface
{
    private const ALLOWED_TYPES = ['asset', 'object', 'document'];

    /**
     * @throws InvalidArgumentException
     */
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (
            !$parameters instanceof ColumnFiltersParameterInterface ||
            !$query instanceof DataObjectQueryInterface
        ) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::DATAOBJECT_RELATION->value) as $column) {
            $query = $this->applyRelationFilter($column, $query);
        }

        return $query;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function applyRelationFilter(ColumnFilter $column, QueryInterface $query): QueryInterface
    {
        $filterValue = $column->getFilterValue();

        if (!is_array($filterValue)) {
            throw new InvalidArgumentException('Value for relation filter must be an array');
        }

        $type = $filterValue['type'] ?? null;
        $ids = $filterValue['ids'] ?? null;

        if ($type === null || !in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(
                'Value for relation filter must contain a valid type (asset, object, document)'
            );
        }

        if (!is_array($ids) || $ids === []) {
            throw new InvalidArgumentException('Value for relation filter must contain a non-empty ids array');
        }

        $fieldKey = $column->getKey() . '.' . $type;

        return $query->filterMultiSelect($fieldKey, $ids);
    }
}

