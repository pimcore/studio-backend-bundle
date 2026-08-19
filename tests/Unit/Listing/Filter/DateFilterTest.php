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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Listing\Filter;

use Carbon\Carbon;
use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\DateFilter;
use Pimcore\Model\Element\Note\Listing as NoteListing;
use Pimcore\Model\Element\Recyclebin\Item\Listing as RecycleBinListing;
use Pimcore\Model\Notification\Listing as NotificationListing;

/**
 * @internal
 */
final class DateFilterTest extends Unit
{
    public function testDateFilterOnOperator(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $params = new FilterParameter(columnFilters: [
            ['key' => 'creationDate', 'type' => 'date', 'filterValue' => ['operator' => 'on', 'value' => '2024-06-10']],
        ]);

        $filter->apply($params, $listing);

        $this->assertStringContainsString('`creationDate` BETWEEN :minTime_0 AND :maxTime_0', $listing->getCondition());
    }

    public function testDateFilterFromOperator(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $params = new FilterParameter(columnFilters: [
            ['key' => 'creationDate', 'type' => 'date', 'filterValue' => ['operator' => 'from', 'value' => '2024-06-10']],
        ]);

        $filter->apply($params, $listing);

        $this->assertStringContainsString('`creationDate` >= :filter_0', $listing->getCondition());
    }

    public function testDateFilterToOperator(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $params = new FilterParameter(columnFilters: [
            ['key' => 'creationDate', 'type' => 'date', 'filterValue' => ['operator' => 'to', 'value' => '2024-06-10']],
        ]);

        $filter->apply($params, $listing);

        $this->assertStringContainsString('`creationDate` <= :filter_0', $listing->getCondition());
    }

    /**
     * Regression test for the "Between" date operator (#1924). The UI decomposes a
     * between-range into two conditions on the same key (`from` + `to`). Deriving the
     * bind parameter from the key made both bind to `:creationDate`, so the second value
     * overwrote the first and the query collapsed to `>= to AND <= to` -> empty result.
     * The bind parameters must be unique per applied filter.
     */
    public function testDateFilterBetweenUsesUniqueParameters(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $params = new FilterParameter(columnFilters: [
            ['key' => 'creationDate', 'type' => 'date', 'filterValue' => ['operator' => 'from', 'value' => '2024-06-10']],
            ['key' => 'creationDate', 'type' => 'date', 'filterValue' => ['operator' => 'to', 'value' => '2024-06-20']],
        ]);

        $filter->apply($params, $listing);

        $condition = $listing->getCondition();
        $this->assertStringContainsString('`creationDate` >= :filter_0', $condition);
        $this->assertStringContainsString('`creationDate` <= :filter_1', $condition);
        $this->assertSame(
            [
                'filter_0' => '2024-06-10 00:00:00',
                'filter_1' => '2024-06-20 00:00:00',
            ],
            $listing->getConditionVariables()
        );
    }

    /**
     * Regression test for #1937. The recycle bin `date` column stores a Unix timestamp
     * (int), not a datetime, so comparing it against Carbon datetime strings failed for
     * every operator. For such columns the bound value must be an integer Unix timestamp.
     */
    public function testDateFilterUnixTimestampColumnOnOperator(): void
    {
        $filter = $this->createFilter();
        $listing = new RecycleBinListing();

        $params = new FilterParameter(columnFilters: [
            ['key' => 'date', 'type' => 'date', 'filterValue' => ['operator' => 'on', 'value' => '2024-06-10']],
        ]);

        $filter->apply($params, $listing);

        $this->assertStringContainsString('`date` BETWEEN :minTime_0 AND :maxTime_0', $listing->getCondition());
        $this->assertSame(
            [
                'minTime_0' => (new Carbon('2024-06-10'))->getTimestamp(),
                'maxTime_0' => (new Carbon('2024-06-10'))->addDay()->subSecond()->getTimestamp(),
            ],
            $listing->getConditionVariables()
        );
    }

    /**
     * The Unix-timestamp handling must apply to range operators too (#1937).
     */
    public function testDateFilterUnixTimestampColumnFromOperator(): void
    {
        $filter = $this->createFilter();
        $listing = new RecycleBinListing();

        $params = new FilterParameter(columnFilters: [
            ['key' => 'date', 'type' => 'date', 'filterValue' => ['operator' => 'from', 'value' => '2024-06-10']],
        ]);

        $filter->apply($params, $listing);

        $this->assertStringContainsString('`date` >= :filter_0', $listing->getCondition());
        $this->assertSame(
            ['filter_0' => (new Carbon('2024-06-10'))->getTimestamp()],
            $listing->getConditionVariables()
        );
    }

    /**
     * Notification creationDate/modificationDate are stored in UTC (pimcore core migration
     * Version20230321133700, writer aligned in pimcore/pimcore#19373). The filter value is a
     * wall-clock date in the application timezone, so it has to be converted to UTC -
     * otherwise the filtered day window is shifted by the timezone offset.
     */
    public function testDateFilterUtcColumnConvertsApplicationTimezoneToUtc(): void
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        try {
            $filter = $this->createFilter();
            $listing = new NotificationListing();

            $params = new FilterParameter(columnFilters: [
                ['key' => 'creationDate', 'type' => 'date', 'filterValue' => ['operator' => 'on', 'value' => '2026-07-01']],
            ]);

            $filter->apply($params, $listing);

            // 2026-07-01 00:00 Europe/Berlin (CEST, UTC+2) is 2026-06-30 22:00 UTC
            $this->assertSame(
                [
                    'minTime_0' => '2026-06-30 22:00:00',
                    'maxTime_0' => '2026-07-01 21:59:59',
                ],
                $listing->getConditionVariables()
            );
        } finally {
            date_default_timezone_set($originalTimezone);
        }
    }

    public function testDateFilterUtcColumnRangeOperator(): void
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        try {
            $filter = $this->createFilter();
            $listing = new NotificationListing();

            $params = new FilterParameter(columnFilters: [
                ['key' => 'creationDate', 'type' => 'date', 'filterValue' => ['operator' => 'from', 'value' => '2026-07-01']],
            ]);

            $filter->apply($params, $listing);

            $this->assertSame(
                ['filter_0' => '2026-06-30 22:00:00'],
                $listing->getConditionVariables()
            );
        } finally {
            date_default_timezone_set($originalTimezone);
        }
    }

    /**
     * The day window must cover exactly the local calendar day even across DST
     * transitions, where a local day is 23 or 25 hours long. Boundaries are therefore
     * derived in the application timezone and only converted to UTC when binding -
     * converting first and adding 24h would bleed an hour into the neighbouring day.
     */
    public function testDateFilterUtcColumnKeepsLocalDayLengthAcrossDstTransitions(): void
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        try {
            $filter = $this->createFilter();

            // Spring transition: 2026-03-29 is a 23-hour day (CET +01:00 -> CEST +02:00)
            $listing = new NotificationListing();
            $params = new FilterParameter(columnFilters: [
                ['key' => 'creationDate', 'type' => 'date', 'filterValue' => ['operator' => 'on', 'value' => '2026-03-29']],
            ]);
            $filter->apply($params, $listing);

            $this->assertSame(
                [
                    'minTime_0' => '2026-03-28 23:00:00',
                    'maxTime_0' => '2026-03-29 21:59:59',
                ],
                $listing->getConditionVariables()
            );

            // Autumn transition: 2026-10-25 is a 25-hour day (CEST +02:00 -> CET +01:00)
            $listing = new NotificationListing();
            $params = new FilterParameter(columnFilters: [
                ['key' => 'creationDate', 'type' => 'date', 'filterValue' => ['operator' => 'on', 'value' => '2026-10-25']],
            ]);
            $filter->apply($params, $listing);

            $this->assertSame(
                [
                    'minTime_0' => '2026-10-24 22:00:00',
                    'maxTime_0' => '2026-10-25 22:59:59',
                ],
                $listing->getConditionVariables()
            );
        } finally {
            date_default_timezone_set($originalTimezone);
        }
    }

    /**
     * Columns not registered as UTC keep their wall-clock comparison - the conversion
     * must not leak into other listings.
     */
    public function testDateFilterNonUtcColumnKeepsWallClockValue(): void
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        try {
            $filter = $this->createFilter();
            $listing = new NoteListing();

            $params = new FilterParameter(columnFilters: [
                ['key' => 'creationDate', 'type' => 'date', 'filterValue' => ['operator' => 'from', 'value' => '2026-07-01']],
            ]);

            $filter->apply($params, $listing);

            $this->assertSame(
                ['filter_0' => '2026-07-01 00:00:00'],
                $listing->getConditionVariables()
            );
        } finally {
            date_default_timezone_set($originalTimezone);
        }
    }

    public function testDateFilterEscape(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $injectionKey = 'id` BETWEEN 0 AND 99999999999) AND SLEEP(3)-- ';
        $params = new FilterParameter(columnFilters: [
            ['key' => $injectionKey, 'type' => 'date', 'filterValue' => ['operator' => 'on', 'value' => '2024-01-01']],
        ]);

        $filter->apply($params, $listing);

        $condition = $listing->getCondition();
        $this->assertStringContainsString('`id``', $condition);
        $this->assertStringNotContainsString('`id` BETWEEN 0', $condition);
    }

    public function testDateFilterThrowsWhenFilterValueNotArray(): void
    {
        $filter = $this->createFilter();
        $listing = new NoteListing();

        $params = new FilterParameter(columnFilters: [
            ['key' => 'creationDate', 'type' => 'date', 'filterValue' => 'not-an-array'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for date must be an array');
        $filter->apply($params, $listing);
    }

    private function createFilter(): DateFilter
    {
        $connection = $this->makeEmpty(Connection::class, [
            'quoteIdentifier' => function (string $identifier): string {
                return '`' . str_replace('`', '``', $identifier) . '`';
            },
        ]);
        $dbResolver = $this->makeEmpty(DbResolverInterface::class, [
            'get' => $connection,
        ]);

        return new DateFilter($dbResolver);
    }
}
