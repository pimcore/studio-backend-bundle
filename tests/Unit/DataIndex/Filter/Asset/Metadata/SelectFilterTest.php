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

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\FilterType;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\SelectFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 * @covers \Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\SelectFilter
 */
final class SelectFilterTest extends TestCase
{
    use ColumnFilterMockTrait;

    public function testIsExceptionIsThrownWhenFilterIsNotAString(): void
    {
        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->never())->method('filterMetadata');

        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 123);

        $stringFilter = new SelectFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for select must be a string');
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplySelectFilter(): void
    {
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 'value');

        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->once())
            ->method('filterMetadata')
            ->with('key', FilterType::SELECT->value, 'value')
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $textAreaFilter = new SelectFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }
}
