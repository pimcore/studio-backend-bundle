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

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\StringFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class StringFilterTest extends Unit
{
    public function testIsExceptionIsThrownWhenFilterIsNotAString(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'wildcardSearch' => Expected::never(),
        ]);

        $columnFilterMock = $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () {
                return [
                    new ColumnFilter('key', 'type', 123),
                ];
            },
        ]);

        $stringFilter = new StringFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for this filter must be a string');
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyStringFilter(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'wildcardSearch' => Expected::once(function ($key, $value) {
                $this->assertSame('key', $key);
                $this->assertSame('value', $value);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $columnFilterMock = $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () {
                return [
                    new ColumnFilter('key', 'type', 'value'),
                ];
            },
        ]);

        $stringFilter = new StringFilter();
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyStringFilterMapsClassnameColumnKeyToIndexField(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'wildcardSearch' => Expected::once(function ($key, $value) {
                $this->assertSame('className', $key);
                $this->assertSame('Car', $value);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $columnFilterMock = $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () {
                return [
                    new ColumnFilter('classname', 'system.string', 'Car'),
                ];
            },
        ]);

        $stringFilter = new StringFilter();
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyStringFilterWithListOfValuesUsesExactMatch(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'wildcardSearch' => Expected::never(),
            'filterMultiSelect' => Expected::once(function ($key, $values) {
                $this->assertSame('sku', $key);
                $this->assertSame(['A-1', 'B-2', 'C 3'], $values);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $stringFilter = new StringFilter();
        $stringFilter->apply($this->getParameterMock(['A-1', ' B-2 ', '', 'A-1', 'C 3', '  ']), $queryMock);
    }

    public function testApplyStringFilterWithListOfValuesMapsClassnameColumnKeyToIndexField(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMultiSelect' => Expected::once(function ($key, $values) {
                $this->assertSame('className', $key);
                $this->assertSame(['Car', 'Boat'], $values);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $stringFilter = new StringFilter();
        $stringFilter->apply($this->getParameterMock(['Car', 'Boat'], 'classname'), $queryMock);
    }

    public function testEmptyListOfValuesDoesNotFilter(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'wildcardSearch' => Expected::never(),
            'filterMultiSelect' => Expected::never(),
        ]);

        $stringFilter = new StringFilter();
        $stringFilter->apply($this->getParameterMock(['', '  ']), $queryMock);
        $stringFilter->apply($this->getParameterMock([]), $queryMock);
    }

    public function testExceptionIsThrownWhenListContainsNonStringValue(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMultiSelect' => Expected::never(),
        ]);

        $stringFilter = new StringFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Each value of the filter must be a string');
        $stringFilter->apply($this->getParameterMock(['A-1', 2]), $queryMock);
    }

    public function testExceptionIsThrownWhenListContainsTooManyValues(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMultiSelect' => Expected::never(),
        ]);

        $stringFilter = new StringFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The filter accepts at most 1000 values');
        $stringFilter->apply($this->getParameterMock($this->getValues(1001)), $queryMock);
    }

    public function testListOfValuesAtTheLimitIsAccepted(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMultiSelect' => Expected::once(function ($key, $values) {
                $this->assertCount(1000, $values);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $stringFilter = new StringFilter();
        $stringFilter->apply($this->getParameterMock($this->getValues(1000)), $queryMock);
    }

    private function getValues(int $count): array
    {
        $values = [];
        for ($i = 0; $i < $count; $i++) {
            $values[] = 'value-' . $i;
        }

        return $values;
    }

    private function getParameterMock(array $filterValue, string $key = 'sku'): ColumnFiltersParameterInterface
    {
        return $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () use ($key, $filterValue) {
                return [
                    new ColumnFilter($key, 'system.string', $filterValue),
                ];
            },
        ]);
    }
}
