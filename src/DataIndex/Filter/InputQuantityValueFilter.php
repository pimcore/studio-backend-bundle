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
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class InputQuantityValueFilter implements FilterInterface
{
    use NumberFilterTrait;

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::SYSTEM_INPUT_QUANTITY_VALUE->value) as $column) {
            $filterValue = $column->getFilterValue();
            if (!isset($filterValue['unitId'], $filterValue['value'])) {
                throw new InvalidArgumentException('Filter value must contain unitId and value');
            }

            $query->wildcardSearch($column->getKey().'.unitId', $filterValue['unitId'])
                ->wildcardSearch($column->getKey().'.value', $filterValue['value']);
        }

        return $query;
    }
}
