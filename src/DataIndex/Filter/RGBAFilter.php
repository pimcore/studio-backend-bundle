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
final class RGBAFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::SYSTEM_RGBA->value) as $column) {

            $filterValue = $column->getFilterValue();

            if (!isset($filterValue['r'], $filterValue['g'], $filterValue['b'], $filterValue['a'])) {
                throw new InvalidArgumentException('Value must contain r,g,b,a');
            }

            $query->filterInteger($column->getKey().'.r', $filterValue['r'])
                ->filterInteger($column->getKey().'.g', $filterValue['g'])
                ->filterInteger($column->getKey().'.b', $filterValue['b'])
                ->filterInteger($column->getKey().'.a', $filterValue['a']);
        }

        return $query;
    }
}
