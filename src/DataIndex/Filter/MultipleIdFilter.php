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
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFiltersParameterInterface;
use function is_array;
use function is_int;

/**
 * @internal
 */
final class MultipleIdFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof SimpleColumnFiltersParameterInterface) {
            return $query;
        }

        $filter = $parameters->getSimpleColumnFilterByType('system.ids');

        if (!$filter) {
            return $query;
        }

        if (!is_array($filter->getFilterValue())) {
            throw new InvalidArgumentException('Filter value for this filter must be an array of integers.');
        }

        $ids = [];

        foreach ($filter->getFilterValue() as $value) {
            if (!is_int($value)) {
                throw new InvalidArgumentException('Each filter value for this filter must be an integer.');
            }
            $ids[] = $value;
        }

        $query->searchByIds($ids);

        return $query;
    }
}
