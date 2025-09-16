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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\Asset\System;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\StringFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 * @covers \Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\StringFilter
 */
final class StringFilterTest extends TestCase
{
    public function testIsExceptionIsThrownWhenFilterIsNotAString(): void
    {
        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->never())->method('wildcardSearch');

        $columnFilterMock = $this->createMock(ColumnFiltersParameterInterface::class);
        $columnFilterMock->method('getColumnFilterByType')->willReturn([
            new ColumnFilter('key', 'type', 123),
        ]);

        $stringFilter = new StringFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for this filter must be a string');
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyStringFilter(): void
    {
        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->once())
            ->method('wildcardSearch')
            ->with('key', 'value')
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $columnFilterMock = $this->createMock(ColumnFiltersParameterInterface::class);
        $columnFilterMock->method('getColumnFilterByType')->willReturn([
            new ColumnFilter('key', 'type', 'value'),
        ]);

        $stringFilter = new StringFilter();
        $stringFilter->apply($columnFilterMock, $queryMock);
    }
}
