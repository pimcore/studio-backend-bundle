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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DatetimeFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\Asset\Metadata\ColumnFilterMockTrait;

/**
 * @internal
 */
#[CoversClass(DatetimeFilter::class)]
#[UsesClass(ColumnFilter::class)]
#[UsesClass(SimpleColumnFilter::class)]
#[UsesClass(InvalidArgumentException::class)]
#[UsesClass(AbstractApiException::class)]
final class DatetimeFilterTest extends TestCase
{
    use ColumnFilterMockTrait;

    private const TEST_DATETIME = '2025-06-10T00:00:00+00:00';

    public function testDateTimeFilterWhenNoArrayIsGivenAsFilterValue(): void
    {
        $datetimeFilter = new DatetimeFilter();
        $columnParameterMock = $this->createMock(ColumnFiltersParameterInterface::class);
        $columnParameterMock->method('getColumnFilterByType')->willReturn([
            new ColumnFilter('key', 'type', 123),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for this filter must be an array');
        $datetimeFilter->apply($columnParameterMock, $this->createMock(AssetQueryInterface::class));
    }

    public function testDateTimeFilterWithOn(): void
    {
        $time = Carbon::parse(self::TEST_DATETIME);
        $datetimeFilter = new DatetimeFilter();
        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->once())
            ->method('filterDatetime')
            ->with('key', null, null, $this->callback(function ($on) use ($time) {
                return $on->toDateTimeString() === $time->toDateTimeString();
            }))
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $columnParameterMock = $this->getColumnFilterMock('key', 'type', ['on' => $time]);

        $datetimeFilter->apply($columnParameterMock, $queryMock);
    }

    public function testDateTimeFilterWithFrom(): void
    {
        $time = Carbon::parse(self::TEST_DATETIME);
        $datetimeFilter = new DatetimeFilter();
        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->once())
            ->method('filterDatetime')
            ->with('key', $this->callback(function ($start) use ($time) {
                return $start->toDateTimeString() === $time->toDateTimeString();
            }), null, null)
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $columnParameterMock = $this->getColumnFilterMock('key', 'type', ['from' => $time]);

        $datetimeFilter->apply($columnParameterMock, $queryMock);
    }

    public function testDateTimeFilterWithTo(): void
    {
        $time = Carbon::parse(self::TEST_DATETIME);
        $datetimeFilter = new DatetimeFilter();
        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->once())
            ->method('filterDatetime')
            ->with('key', null, $this->callback(function ($end) use ($time) {
                return $end->toDateTimeString() === $time->toDateTimeString();
            }), null)
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $columnParameterMock = $this->getColumnFilterMock('key', 'type', ['to' => $time]);

        $datetimeFilter->apply($columnParameterMock, $queryMock);
    }
}
