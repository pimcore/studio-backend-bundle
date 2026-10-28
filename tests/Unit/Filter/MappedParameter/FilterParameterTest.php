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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Filter\MappedParameter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterOperator;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use function iterator_to_array;

/**
 * @internal
 */
final class FilterParameterTest extends Unit
{
    private const STRING_TYPE = 'system.string';

    public function testOperatorFiltersAreNotReturnedByType(): void
    {
        $parameter = new FilterParameter(columnFilters: [
            ['key' => 'sku', 'type' => self::STRING_TYPE, 'filterValue' => 'abc'],
            ['key' => 'name', 'type' => self::STRING_TYPE, 'operator' => 'empty'],
        ]);

        $filters = iterator_to_array($parameter->getColumnFilterByType(self::STRING_TYPE), false);

        $this->assertCount(1, $filters);
        $this->assertSame('sku', $filters[0]->getKey());
        $this->assertNull($filters[0]->getOperator());
    }

    public function testOperatorFiltersAreReturnedSeparately(): void
    {
        $parameter = new FilterParameter(columnFilters: [
            ['key' => 'sku', 'type' => self::STRING_TYPE, 'filterValue' => 'abc'],
            ['key' => 'name', 'type' => self::STRING_TYPE, 'operator' => 'empty', 'locale' => 'en'],
            ['key' => 'category', 'type' => 'dataobject.relation', 'operator' => 'notEmpty', 'filterValue' => null],
        ]);

        $filters = iterator_to_array($parameter->getOperatorColumnFilters(), false);

        $this->assertCount(2, $filters);
        $this->assertSame('name.en', $filters[0]->getKey());
        $this->assertSame(self::STRING_TYPE, $filters[0]->getType());
        $this->assertSame(FilterOperator::EMPTY, $filters[0]->getOperator());
        $this->assertSame('category', $filters[1]->getKey());
        $this->assertSame(FilterOperator::NOT_EMPTY, $filters[1]->getOperator());
    }

    public function testNoOperatorFiltersWithoutOperators(): void
    {
        $parameter = new FilterParameter(columnFilters: [
            ['key' => 'sku', 'type' => self::STRING_TYPE, 'filterValue' => 'abc'],
        ]);

        $this->assertSame([], iterator_to_array($parameter->getOperatorColumnFilters(), false));
    }

    public function testNullOperatorIsTreatedAsNoOperator(): void
    {
        $parameter = new FilterParameter(columnFilters: [
            ['key' => 'sku', 'type' => self::STRING_TYPE, 'filterValue' => 'abc', 'operator' => null],
        ]);

        $this->assertCount(1, iterator_to_array($parameter->getColumnFilterByType(self::STRING_TYPE), false));
        $this->assertSame([], iterator_to_array($parameter->getOperatorColumnFilters(), false));
    }

    public function testUnknownOperatorIsRejected(): void
    {
        $parameter = new FilterParameter(columnFilters: [
            ['key' => 'sku', 'type' => self::STRING_TYPE, 'operator' => 'contains'],
        ]);

        $this->expectException(InvalidArgumentException::class);

        iterator_to_array($parameter->getOperatorColumnFilters());
    }

    public function testOperatorFilterNeedsKeyAndType(): void
    {
        $parameter = new FilterParameter(columnFilters: [
            ['key' => 'sku', 'operator' => 'empty'],
        ]);

        $this->expectException(InvalidArgumentException::class);

        iterator_to_array($parameter->getOperatorColumnFilters());
    }

    public function testValueFilterStillNeedsAFilterValue(): void
    {
        $parameter = new FilterParameter(columnFilters: [
            ['key' => 'sku', 'type' => self::STRING_TYPE],
        ]);

        $this->expectException(InvalidArgumentException::class);

        iterator_to_array($parameter->getColumnFilterByType(self::STRING_TYPE));
    }
}
