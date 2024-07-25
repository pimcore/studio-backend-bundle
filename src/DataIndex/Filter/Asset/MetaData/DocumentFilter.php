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
final class DocumentFilter implements FilterInterface
{
    use IsAssetMetaDataTrait;

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        $parameters = $this->validateParameterType($parameters);
        $assetQuery = $this->validateQueryType($query);

        if (!$parameters || !$assetQuery) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::METADATA_DOCUMENT->value) as $column) {
            $assetQuery = $this->applyDocumentFilter($column, $assetQuery);
        }

        return $assetQuery;
    }

    private function applyDocumentFilter(ColumnFilter $column, AssetQuery $query): AssetQuery
    {
        if (!is_int($column->getFilterValue())) {
            throw new InvalidArgumentException('Filter value for document must be a integer (ID of the document)');
        }

        $query->filterMetaData($column->getKey(), FilterType::DOCUMENT->value, $column->getFilterValue());

        return $query;
    }
}
