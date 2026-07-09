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

namespace Pimcore\Bundle\StudioBackendBundle\Listing\Filter;

use Carbon\Carbon;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Model\Element\Recyclebin\Item\Listing as RecycleBinListing;
use Pimcore\Model\Listing\AbstractListing;
use function is_array;

/**
 * @internal
 */
final readonly class DateFilter implements FilterInterface
{
    /**
     * Listings whose date columns are stored as Unix timestamps (integer) rather than a
     * native DATE/DATETIME/TIMESTAMP. Such columns must be compared against an integer
     * timestamp instead of a datetime string, otherwise MySQL coerces the string to a
     * number and the comparison never matches. Keyed by listing class => column keys.
     */
    private const UNIX_TIMESTAMP_COLUMNS = [
        RecycleBinListing::class => ['date'],
    ];

    public function __construct(
        private DbResolverInterface $dbResolver,
    ) {
    }

    public function apply(
        mixed $parameters,
        mixed $listing
    ): mixed {
        $index = 0;
        foreach ($parameters->getColumnFilterByType(FilterType::DATE->value) as $column) {
            $listing = $this->applyDateFilter($column, $listing, $index);
            $index++;
        }

        return $listing;
    }

    private function applyDateFilter(ColumnFilter $column, mixed $listing, int $index): mixed
    {
        if (!is_array($column->getFilterValue())) {
            throw new InvalidArgumentException('Filter value for date must be an array');
        }

        $filter = $column->getFilterValue();
        $quotedKey = $this->dbResolver->get()->quoteIdentifier($column->getKey());
        $carbonDate = new Carbon($filter['value']);
        $isUnixTimestamp = $this->isUnixTimestampColumn($listing, $column->getKeyWithOutLocale());

        // The bind parameter name must be unique per applied filter. Deriving it from the
        // column key collides when the same column is filtered twice (a "between" range is
        // sent as `from` + `to` on the same key), silently dropping the first value.
        if ($filter['operator'] === 'on') {
            $dateCondition = $quotedKey . ' BETWEEN :minTime_' . $index . ' AND :maxTime_' . $index;
            $listing->addConditionParam(
                $dateCondition,
                [
                    'minTime_' . $index => $this->formatValue($carbonDate, $isUnixTimestamp),
                    'maxTime_' . $index => $this->formatValue(
                        $carbonDate->copy()->addDay()->subSecond(),
                        $isUnixTimestamp
                    ),
                ]
            );

            return $listing;
        }

        $parameterName = 'filter_' . $index;
        $dateCondition = $quotedKey . ' ' .
            $this->matchNumericOperator($filter['operator']) .
            ' :' . $parameterName;
        $listing->addConditionParam(
            $dateCondition,
            [$parameterName => $this->formatValue($carbonDate, $isUnixTimestamp)]
        );

        return $listing;
    }

    private function formatValue(Carbon $date, bool $asUnixTimestamp): int|string
    {
        return $asUnixTimestamp ? $date->getTimestamp() : $date->toDateTimeString();
    }

    private function isUnixTimestampColumn(mixed $listing, string $key): bool
    {
        foreach (self::UNIX_TIMESTAMP_COLUMNS as $listingClass => $columns) {
            if ($listing instanceof $listingClass && in_array($key, $columns, true)) {
                return true;
            }
        }

        return false;
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
