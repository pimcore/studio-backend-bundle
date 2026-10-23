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
use function is_string;

/**
 * @internal
 */
final class StringFilter implements FilterInterface
{
    use IsAssetFilterTrait;
    use MapsSystemColumnFieldTrait;

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
        if (!is_string($column->getFilterValue())) {
            throw new InvalidArgumentException('Filter value for this filter must be a string');
        }

        $query->wildcardSearch($this->mapColumnKeyToIndexField($column->getKey()), $column->getFilterValue());

        return $query;
    }
}
