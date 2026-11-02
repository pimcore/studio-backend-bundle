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

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\SelectFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use function func_get_args;

/**
 * @internal
 */
final class SelectFilterTest extends Unit
{
    use ColumnFilterMockTrait;

    public function testQueryIsReturnedUntouchedWhenParametersAreNotColumnFilters(): void
    {
        $query = $this->makeEmpty(QueryInterface::class, [
            'filterMultiSelect' => Expected::never(),
        ]);

        $this->assertSame($query, (new SelectFilter())->apply('invalid', $query));
    }

    /**
     * In the data-object grid an empty value stands for "no value", so it also matches objects where the field is
     * missing (pimcore/platform-version#394).
     */
    public function testDataObjectQueryMatchesMissingValuesForAnEmptyValue(): void
    {
        $parameter = $this->getColumnFilterMock('color', ColumnType::SYSTEM_SELECT->value, ['', 'red']);

        $filteredQuery = $this->makeEmpty(DataObjectQueryInterface::class);
        $query = $this->makeEmpty(DataObjectQueryInterface::class, [
            'filterMultiSelect' => Expected::once(
                function () use ($filteredQuery) {
                    $this->assertSame(['color', ['', 'red'], true, true], func_get_args());

                    return $filteredQuery;
                }
            ),
        ]);

        $this->assertSame($filteredQuery, (new SelectFilter())->apply($parameter, $query));
    }

    /**
     * Asset and document queries keep the plain multi select behaviour.
     */
    public function testOtherQueriesKeepThePlainMultiSelect(): void
    {
        $parameter = $this->getColumnFilterMock('color', ColumnType::SYSTEM_SELECT->value, ['']);

        $filteredQuery = $this->makeEmpty(QueryInterface::class);
        $query = $this->makeEmpty(QueryInterface::class, [
            'filterMultiSelect' => Expected::once(
                function () use ($filteredQuery) {
                    $this->assertSame(['color', [''], true], func_get_args());

                    return $filteredQuery;
                }
            ),
        ]);

        $this->assertSame($filteredQuery, (new SelectFilter())->apply($parameter, $query));
    }

    public function testExceptionIsThrownWhenFilterValueIsNotAnArray(): void
    {
        $parameter = $this->getColumnFilterMock('color', ColumnType::SYSTEM_SELECT->value, 'red');

        $query = $this->makeEmpty(DataObjectQueryInterface::class, [
            'filterMultiSelect' => Expected::never(),
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new SelectFilter())->apply($parameter, $query);
    }
}
