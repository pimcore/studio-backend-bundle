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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\System;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\MetaData\IsAssetFilterTrait;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\OpenSearchFieldMappingInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;

/**
 * @internal
 */
final class DatetimeFilter implements FilterInterface
{
    use IsAssetFilterTrait;

    public function __construct(
        private readonly OpenSearchFieldMappingInterface $openSearchFieldMapping,
    )
    {
    }

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        $parameters = $this->validateParameterType($parameters);
        $assetQuery = $this->validateQueryType($query);

        if (!$parameters || !$assetQuery) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::SYSTEM_DATETIME->value) as $column) {
            $assetQuery = $this->applyDatetimeFilter($column, $assetQuery);
        }

        return $assetQuery;
    }

    private function applyDatetimeFilter(ColumnFilter $column, AssetQuery $query): AssetQuery
    {
        if (!is_array($column->getFilterValue())) {
            throw new InvalidArgumentException('Filter value for this filter must be an array');
        }

        $key = $this->openSearchFieldMapping->getOpenSearchKey($column->getKey());

        $filterValue = $column->getFilterValue();

        if (isset($filterValue['on'])) {
            $query->filterDatetime($key, null, $filterValue['on']);
        }

        if (isset($filterValue['to'])) {
            $query->filterDatetime($key, null, $filterValue['to']);
        }

        if (isset($filterValue['from'])) {
            $query->filterDatetime($key, $filterValue['from']);
        }

        return $query;
    }
}
