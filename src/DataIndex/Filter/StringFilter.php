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
use function count;
use function is_array;
use function is_string;

/**
 * @internal
 */
final class StringFilter implements FilterInterface
{
    use IsAssetFilterTrait;
    use MapsSystemColumnFieldTrait;

    private const int MAX_VALUES = 1000;

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        $parameters = $this->validateParameterType($parameters);

        if (!$parameters) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::SYSTEM_STRING->value) as $column) {
            $query = $this->applyStringFilter($column, $query);
        }

        return $query;
    }

    private function applyStringFilter(ColumnFilter $column, QueryInterface $query): QueryInterface
    {
        $filterValue = $column->getFilterValue();

        if (is_array($filterValue)) {
            return $this->applyMultiValueFilter($column, $filterValue, $query);
        }

        if (!is_string($filterValue)) {
            throw new InvalidArgumentException('Filter value for this filter must be a string or an array of strings');
        }

        $query->wildcardSearch($this->mapColumnKeyToIndexField($column->getKey()), $filterValue);

        return $query;
    }

    /**
     * A list of values matches rows whose field equals any of the values exactly.
     */
    private function applyMultiValueFilter(
        ColumnFilter $column,
        array $filterValue,
        QueryInterface $query
    ): QueryInterface {
        $values = [];

        foreach ($filterValue as $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException('Each value of the filter must be a string');
            }

            $value = trim($value);

            if ($value !== '') {
                $values[$value] = $value;
            }
        }

        if ($values === []) {
            return $query;
        }

        if (count($values) > self::MAX_VALUES) {
            throw new InvalidArgumentException('The filter accepts at most ' . self::MAX_VALUES . ' values');
        }

        $query->filterMultiSelect($this->mapColumnKeyToIndexField($column->getKey()), array_values($values));

        return $query;
    }
}
