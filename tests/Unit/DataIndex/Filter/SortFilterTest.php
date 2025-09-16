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

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\SortFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter as SortFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilterParameterInterface;

/**
 * @internal
 * @covers \Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\SortFilter
 */
final class SortFilterTest extends TestCase
{
    public function testIfParameterIsNotInstanceOfSortFilterParameterInterface(): void
    {
        $sortFilter = new SortFilter();
        $query = $this->createMock(AssetQueryInterface::class);
        $query->expects($this->never())->method('orderByField');

        $sortFilter->apply('test', $query);
        
        // Ensure the test passes - when parameter is not an instance of SortFilterParameterInterface,
        // the orderByField method should never be called
        $this->assertTrue(true);
    }

    public function testSortDirectionWithDesc(): void
    {
        $sortFilter = new SortFilter();
        $parameter = $this->createMock(SortFilterParameterInterface::class);
        $parameter->method('getSortFilter')->willReturn(new SortFilterParameter('key', 'desc'));

        $query = $this->createMock(AssetQueryInterface::class);
        $query->expects($this->once())
            ->method('orderByField')
            ->with('key', SortDirection::DESC)
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $sortFilter->apply($parameter, $query);
    }

    public function testSortDirectionWithDefaultValue(): void
    {
        $sortFilter = new SortFilter();
        $parameter = $this->createMock(SortFilterParameterInterface::class);
        $parameter->method('getSortFilter')->willReturn(new SortFilterParameter());

        $query = $this->createMock(AssetQueryInterface::class);
        $query->expects($this->once())
            ->method('orderByField')
            ->with('id', SortDirection::ASC)
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $sortFilter->apply($parameter, $query);
    }
}
