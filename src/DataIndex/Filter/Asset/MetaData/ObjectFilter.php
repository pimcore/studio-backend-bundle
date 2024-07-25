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
use function is_int;

/**
 * @internal
 */
final class ObjectFilter implements FilterInterface
{
    use IsAssetMetaDataTrait;

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        $parameters = $this->validateParameterType($parameters);
        $query = $this->validateQueryType($query);

        if (!$parameters || !$query) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::METADATA_DATA_OBJECT->value) as $column) {
            $query = $this->applyAssetFilter($column, $query);
        }

        return $query;
    }

    private function applyAssetFilter(ColumnFilter $column, AssetQuery $query): AssetQuery
    {
        if (!is_int($column->getFilterValue())) {
            throw new InvalidArgumentException('Filter value for object must be a integer (ID of the object)');
        }

        $query->filterMetaData($column->getKey(), FilterType::OBJECT->value, $column->getFilterValue());

        return $query;
    }
}
