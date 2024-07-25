<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\MetaData;

use Pimcore\Bundle\GenericDataIndexBundle\Model\OpenSearch\Query\DateFilter as GenericDateFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use function is_array;

/**
 * @internal
 */
final class DateFilter implements FilterInterface
{
    use IsAssetMetaDataTrait;

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        $parameters = $this->validateParameterType($parameters);
        $query = $this->validateQueryType($query);

        if (!$parameters || !$query) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::METADATA_DATE->value) as $column) {
            $query = $this->applyDateFilter($column, $query);
        }

        return $query;
    }

    private function applyDateFilter(ColumnFilter $column, AssetQuery $query): AssetQuery
    {
        if (!is_array($column->getFilterValue())) {
            throw new InvalidArgumentException('Filter value for date must be an array');
        }

        $filterValue = $column->getFilterValue();

        if (isset($filterValue['on'])) {
            $query->filterMetaDate(
                $column->getKey(),
                FilterType::DATE->value,
                [GenericDateFilter::PARAM_ON => $filterValue['on']]
            );
        }

        if (isset($filterValue['to'])) {
            $query->filterMetaDate(
                $column->getKey(),
                FilterType::DATE->value,
                [GenericDateFilter::PARAM_END => $filterValue['to']]
            );
        }

        if (isset($filterValue['from'])) {
            $query->filterMetaDate(
                $column->getKey(),
                FilterType::DATE->value,
                [GenericDateFilter::PARAM_START => $filterValue['from']]
            );
        }

        return $query;
    }
}
