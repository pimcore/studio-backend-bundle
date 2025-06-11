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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata;

use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\Query\DateFilter as GenericDateFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\IsAssetFilterTrait;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DateTimeTrait;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use function is_array;

/**
 * @internal
 */
final class DateFilter implements FilterInterface
{
    use IsAssetFilterTrait;
    use DateTimeTrait;


    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        $parameters = $this->validateParameterType($parameters);
        $assetQuery = $this->validateQueryType($query);

        if (!$parameters || !$assetQuery) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::METADATA_DATE->value) as $column) {
            $assetQuery = $this->applyDateFilter($column, $assetQuery);
        }

        return $assetQuery;
    }

    private function applyDateFilter(ColumnFilter $column, AssetQueryInterface $query): AssetQueryInterface
    {

        $this->setFilterValue($column);

        $filterValue = $column->getFilterValue();

        if (isset($filterValue['on'])) {
            $query->filterMetadata(
                $column->getKey(),
                FilterType::DATE->value,
                [GenericDateFilter::PARAM_ON => $this->getOnAsCarbon()]
            );
        }

        if (isset($filterValue['to'])) {
            $query->filterMetadata(
                $column->getKey(),
                FilterType::DATE->value,
                [GenericDateFilter::PARAM_END => $this->getToAsCarbon()]
            );
        }

        if (isset($filterValue['from'])) {
            $query->filterMetadata(
                $column->getKey(),
                FilterType::DATE->value,
                [GenericDateFilter::PARAM_START => $this->getFromAsCarbon()]
            );
        }

        return $query;
    }
}
