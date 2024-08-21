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

namespace Pimcore\Bundle\StudioBackendBundle\Listing\Filter;

use Carbon\Carbon;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Model\Listing\AbstractListing;
use function is_array;

/**
 * @internal
 */
final class DateFilter implements FilterInterface
{
    public function apply(
        mixed $parameters,
        mixed $listing
    ): mixed {
        foreach ($parameters->getColumnFilterByType(FilterType::DATE->value) as $column) {
            $listing = $this->applyDateFilter($column, $listing);
        }

        return $listing;
    }

    private function applyDateFilter(ColumnFilter $column, mixed $listing): mixed
    {
        if (!is_array($column->getFilterValue())) {
            throw new InvalidArgumentException('Filter value for date must be an array');
        }

        $filter = $column->getFilterValue();
        $key = $column->getKey();
        $carbonDate = new Carbon($filter['value']);
        $value = $carbonDate->toDateTimeString();
        if ($filter['operator'] === 'on') {
            $dateCondition = '`' . $key . '` ' . ' BETWEEN :minTime AND :maxTime';
            $listing->addConditionParam(
                $dateCondition,
                [
                    'minTime' => $value,
                    'maxTime' => $carbonDate->addDay()->subSecond()->toDateTimeString()
                ]
            );

            return $listing;
        }

        $dateCondition = '`' . $key . '` ' .
            $this->matchNumericOperator($filter['operator']) .
            ' :' . $key;
        $listing->addConditionParam(
            $dateCondition,
            [$key => $value]
        );

        return $listing;
    }

    public function supports(mixed $listing): bool
    {
        return $listing instanceof AbstractListing;
    }

    private function matchNumericOperator(string $operator): string
    {
        return match ($operator) {
            'to' => '<=',
            'from' => '>=',
            default => '='
        };
    }
}
