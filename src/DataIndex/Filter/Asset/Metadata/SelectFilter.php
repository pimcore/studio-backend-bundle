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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\IsAssetFilterTrait;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use function is_string;

/**
 * @internal
 */
final class SelectFilter implements FilterInterface
{
    use IsAssetFilterTrait;

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        $parameters = $this->validateParameterType($parameters);
        $assetQuery = $this->validateQueryType($query);

        if (!$parameters || !$assetQuery) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::METADATA_SELECT->value) as $column) {
            $assetQuery = $this->applySelectFilter($column, $assetQuery);
        }

        return $assetQuery;
    }

    private function applySelectFilter(ColumnFilter $column, AssetQueryInterface $query): AssetQueryInterface
    {
        if (!is_string($column->getFilterValue())) {
            throw new InvalidArgumentException('Filter value for select must be a string');
        }

        $query->filterMetadata($column->getKey(), FilterType::SELECT->value, $column->getFilterValue());

        return $query;
    }
}
