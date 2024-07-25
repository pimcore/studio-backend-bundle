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

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use function is_string;

/**
 * @internal
 */
final class SelectFilter implements FilterInterface
{
    use IsAssetMetaDataTrait;

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        /** @var ColumnFiltersParameterInterface $parameters */
        /** @var AssetQuery $query */
        if (!$this->isAssetMetaData($parameters, $query)) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::METADATA_SELECT->value) as $column) {
            $query = $this->applySelectFilter($column, $query);
        }

        return $query;
    }

    private function applySelectFilter(ColumnFilter $column, AssetQuery $query): AssetQuery
    {
        if (!is_string($column->getFilterValue())) {
            throw new InvalidArgumentException('Filter value for select must be a string');
        }

        $query->filterMetaDate($column->getKey(), FilterType::SELECT->value, $column->getFilterValue());

        return $query;
    }
}
