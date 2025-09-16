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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FullTextFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFiltersParameterInterface;

/**
 * @internal
 * @covers \Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FullTextFilter
 */
final class FullTextFilterTest extends TestCase
{
    public function testIsExceptionThrownWhenFilterIsNotAString(): void
    {
        $columnFilter = new SimpleColumnFilter('system.fulltext', 1);
        $parameter = $this->createMock(SimpleColumnFiltersParameterInterface::class);
        $parameter->method('getSimpleColumnFilterByType')->willReturn($columnFilter);

        $query = $this->createMock(QueryInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for the fulltext filter must be a string');

        $filter = new FullTextFilter();
        $filter->apply($parameter, $query);
    }

    public function testIfFilterFullTextIsCalled(): void
    {
        $columnFilter = new SimpleColumnFilter('system.fulltext', 'term');
        $parameter = $this->createMock(SimpleColumnFiltersParameterInterface::class);
        $parameter->method('getSimpleColumnFilterByType')->willReturn($columnFilter);

        $query = $this->createMock(QueryInterface::class);
        $query->expects($this->once())
            ->method('filterFullText')
            ->with('term')
            ->willReturn($this->createMock(QueryInterface::class));

        $filter = new FullTextFilter();
        $filter->apply($parameter, $query);
    }
}
