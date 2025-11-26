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

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\IsAssetFilterTrait;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use function is_array;

/**
 * @internal
 */
final class DateTimeFilter implements FilterInterface
{
    use IsAssetFilterTrait;
    use DateTimeTrait;

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::SYSTEM_DATETIME->value) as $column) {
            $query = $this->applySystemDatetimeFilter($column, $query, false);
        }

        return $query;
    }
}
