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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\Asset\System;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\System\DatetimeFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\Asset\Metadata\ColumnFilterMockTrait;

/**
 * @internal
 */
final class DatetimeFilterTest extends Unit
{
    use ColumnFilterMockTrait;

    public function testDateTimeFilterWhenNoArrayIsGivenAsFilterValue(): void
    {
        $datetimeFilter = new DatetimeFilter();
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
        $time = 1726753660;
        $datetimeFilter = new DatetimeFilter();
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterDatetime' => Expected::once(function ($key, $start, $end, $on) use ($time) {
                $this->assertSame('key', $key);
                $this->assertNull($start);
                $this->assertNull($end);
                $this->assertSame($time, $on);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $columnParameterMock = $this->getColumnFilterMock('key', 'type', ['on' => $time]);

        $datetimeFilter->apply($columnParameterMock, $queryMock);

    }

    public function testDateTimeFilterWithFrom(): void
    {
        $time = 1726753660;
        $datetimeFilter = new DatetimeFilter();
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterDatetime' => Expected::once(function ($key, $start, $end, $on) use ($time) {
                $this->assertSame('key', $key);
                $this->assertSame($time, $start);
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
        $time = 1726753660;
        $datetimeFilter = new DatetimeFilter();
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterDatetime' => Expected::once(function ($key, $start, $end, $on) use ($time) {
                $this->assertSame('key', $key);
                $this->assertNull($start);
                $this->assertSame($time, $end);
                $this->assertNull($on);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $columnParameterMock = $this->getColumnFilterMock('key', 'type', ['to' => $time]);

        $datetimeFilter->apply($columnParameterMock, $queryMock);
    }
}
