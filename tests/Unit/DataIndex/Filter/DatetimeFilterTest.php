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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DateTimeFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class DatetimeFilterTest extends Unit
{
    use ColumnFilterMockTrait;

    public function testDateTimeFilterWhenNoArrayIsGivenAsFilterValue(): void
    {
        $datetimeFilter = new DateTimeFilter();
        $columnParameterMock = $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () {
                return  [
                    new ColumnFilter('key', 'type', 123),
                ];
            },
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for this filter must be an array');
        $datetimeFilter->apply($columnParameterMock, $this->makeEmpty(AssetQueryInterface::class));
    }

    public function testDateTimeFilterWithOn(): void
    {
        $time = Carbon::parse('2025-06-10T00:00:00+00:00');
        $datetimeFilter = new DateTimeFilter();
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterDatetime' => Expected::once(function ($key, $start, $end, $on) use ($time) {
                $this->assertSame('key', $key);
                $this->assertNull($start);
                $this->assertNull($end);
                $this->assertSame($time->toDateTimeString(), $on->toDateTimeString());

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $columnParameterMock = $this->getColumnFilterMock('key', 'type', ['on' => $time]);

        $datetimeFilter->apply($columnParameterMock, $queryMock);

    }

    public function testDateTimeFilterWithFrom(): void
    {
        $time = Carbon::parse('2025-06-10T00:00:00+00:00');
        $datetimeFilter = new DateTimeFilter();
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterDatetime' => Expected::once(function ($key, $start, $end, $on) use ($time) {
                $this->assertSame('key', $key);
                $this->assertSame($time->toDateTimeString(), $start->toDateTimeString());
                $this->assertNull($end);
                $this->assertNull($on);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $columnParameterMock = $this->getColumnFilterMock('key', 'type', ['from' => $time]);

        $datetimeFilter->apply($columnParameterMock, $queryMock);
    }

    public function testDateTimeFilterWithTo(): void
    {
        $time = Carbon::parse('2025-06-10T00:00:00+00:00');
        $datetimeFilter = new DateTimeFilter();
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterDatetime' => Expected::once(function ($key, $start, $end, $on) use ($time) {
                $this->assertSame('key', $key);
                $this->assertNull($start);
                $this->assertSame($time->toDateTimeString(), $end->toDateTimeString());
                $this->assertNull($on);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $columnParameterMock = $this->getColumnFilterMock('key', 'type', ['to' => $time]);

        $datetimeFilter->apply($columnParameterMock, $queryMock);
    }
}
