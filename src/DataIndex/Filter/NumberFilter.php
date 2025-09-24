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
final class NumberFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::SYSTEM_NUMBER->value) as $column) {
            $query = $this->applyNumberFilter($column, $query);
        }

        return $query;
    }

    private function applyNumberFilter(ColumnFilter $column, QueryInterface $query): QueryInterface
    {
        $fiterValue = $column->getFilterValue();

        if (!isset($fiterValue['setting'])) {
            throw new InvalidArgumentException('This filter requires a setting value');
        }
        $setting = $fiterValue['setting'];

        if (isset($fiterValue['is']) && $setting == 'is') {
            return $query->filterNumber($column->getKey(), $fiterValue['is']);
        }

        if (isset($fiterValue['to']) && $setting == 'less') {
            return $query->filterNumberRange($column->getKey(), null, $fiterValue['to']);
        }

        if (isset($fiterValue['from']) && $setting == 'more') {
            return $query->filterNumberRange($column->getKey(), $fiterValue['from'], null);
        }

        if ($setting == 'between') {
            return $query->filterNumberRange($column->getKey(), $fiterValue['from'], $fiterValue['to']);
        }

        throw new InvalidArgumentException('Unable to apply number filter no correct setting given');
    }
}
