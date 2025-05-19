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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\Asset\Metadata;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\Query\DateFilter as GenericDateFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\DateFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\FilterType;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
final class DateFilterTest extends Unit
{
    use ColumnFilterMockTrait;

    public function testIsExceptionIsThrownWhenFilterIsNotAnArray(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::never(),
        ]);

        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 'not_array');

        $stringFilter = new DateFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for date must be an array');
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyDateFilterForOn(): void
    {
        $time = 1726753660;
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', ['on' => $time]);

        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::once(function ($key, $type, $value) use ($time) {
                $this->assertSame('key', $key);
                $this->assertSame(FilterType::DATE->value, $type);
                $this->assertSame([GenericDateFilter::PARAM_ON => $time], $value);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $textAreaFilter = new DateFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyDateFilterForTo(): void
    {
        $time = 1726753660;
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', ['to' => $time]);

        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::once(function ($key, $type, $value) use ($time) {
                $this->assertSame('key', $key);
                $this->assertSame(FilterType::DATE->value, $type);
                $this->assertSame([GenericDateFilter::PARAM_END => $time], $value);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $textAreaFilter = new DateFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyDateFilterForFrom(): void
    {
        $time = 1726753660;
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', ['from' => $time]);

        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::once(function ($key, $type, $value) use ($time) {
                $this->assertSame('key', $key);
                $this->assertSame(FilterType::DATE->value, $type);
                $this->assertSame([GenericDateFilter::PARAM_START => $time], $value);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $textAreaFilter = new DateFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }
}
