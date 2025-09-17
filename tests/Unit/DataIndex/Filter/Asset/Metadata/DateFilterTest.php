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

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\Query\DateFilter as GenericDateFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\DateFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\FilterType;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFilter;

/**
 * @internal
 */
#[CoversClass(DateFilter::class)]
#[UsesClass(ColumnFilter::class)]
#[UsesClass(SimpleColumnFilter::class)]
#[UsesClass(InvalidArgumentException::class)]
#[UsesClass(AbstractApiException::class)]
final class DateFilterTest extends TestCase
{
    use ColumnFilterMockTrait;

    public function testIsExceptionIsThrownWhenFilterIsNotAnArray(): void
    {
        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->never())->method('filterMetadata');

        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 'not_array');

        $stringFilter = new DateFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for this filter must be an array');
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyDateFilterForOn(): void
    {
        $time = Carbon::parse('2025-06-10T00:00:00+00:00');
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', ['on' => $time]);

        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->once())
            ->method('filterMetadata')
            ->with(
                'key',
                FilterType::DATE->value,
                $this->callback(function ($value) use ($time) {
                    return $time->toDateTimeString() === $value[GenericDateFilter::PARAM_ON]->toDateTimeString();
                })
            )
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $textAreaFilter = new DateFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyDateFilterForTo(): void
    {
        $time = Carbon::parse('2025-06-10T00:00:00+00:00');
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', ['to' => $time]);

        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->once())
            ->method('filterMetadata')
            ->with(
                'key',
                FilterType::DATE->value,
                $this->callback(function ($value) use ($time) {
                    return $time->toDateTimeString() === $value[GenericDateFilter::PARAM_END]->toDateTimeString();
                })
            )
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $textAreaFilter = new DateFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyDateFilterForFrom(): void
    {
        $time = Carbon::parse('2025-06-10T00:00:00+00:00');
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', ['from' => $time]);

        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->once())
            ->method('filterMetadata')
            ->with(
                'key',
                FilterType::DATE->value,
                $this->callback(function ($value) use ($time) {
                    return $time->toDateTimeString() === $value[GenericDateFilter::PARAM_START]->toDateTimeString();
                })
            )
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $textAreaFilter = new DateFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }
}
