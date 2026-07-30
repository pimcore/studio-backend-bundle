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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\SystemUserFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class SystemUserFilterTest extends Unit
{
    use ColumnFilterMockTrait;

    public function testQueryIsReturnedUntouchedWhenParametersAreNotColumnFilters(): void
    {
        $query = $this->makeEmpty(QueryInterface::class, [
            'filterMultiSelect' => Expected::never(),
        ]);

        $filter = new SystemUserFilter();

        $this->assertSame($query, $filter->apply('invalid', $query));
    }

    public function testUserIdsAreDelegatedToFilterMultiSelect(): void
    {
        $parameter = $this->getColumnFilterMock(
            'userModification',
            ColumnType::SYSTEM_USER->value,
            [2, 5]
        );

        $filteredQuery = $this->makeEmpty(QueryInterface::class);
        $query = $this->makeEmpty(QueryInterface::class, [
            'filterMultiSelect' => Expected::once(
                function ($fieldKey, $userIds) use ($filteredQuery) {
                    $this->assertSame('userModification', $fieldKey);
                    $this->assertSame([2, 5], $userIds);

                    return $filteredQuery;
                }
            ),
        ]);

        $filter = new SystemUserFilter();

        $this->assertSame($filteredQuery, $filter->apply($parameter, $query));
    }

    public function testEveryUserColumnFilterIsApplied(): void
    {
        $parameter = $this->makeEmpty(ColumnFiltersParameterInterface::class, [
            'getColumnFilterByType' => function () {
                return [
                    new ColumnFilter('userOwner', ColumnType::SYSTEM_USER->value, [2]),
                    new ColumnFilter('userModification', ColumnType::SYSTEM_USER->value, [5, 7]),
                ];
            },
        ]);

        $appliedFilters = [];
        $query = $this->makeEmpty(QueryInterface::class, [
            'filterMultiSelect' => Expected::exactly(
                2,
                function ($fieldKey, $userIds) use (&$appliedFilters, &$query) {
                    $appliedFilters[$fieldKey] = $userIds;

                    return $query;
                }
            ),
        ]);

        $filter = new SystemUserFilter();
        $filter->apply($parameter, $query);

        $this->assertSame(
            ['userOwner' => [2], 'userModification' => [5, 7]],
            $appliedFilters
        );
    }

    public function testExceptionIsThrownWhenFilterValueIsNotAnArray(): void
    {
        $parameter = $this->getColumnFilterMock(
            'userModification',
            ColumnType::SYSTEM_USER->value,
            2
        );

        $query = $this->makeEmpty(QueryInterface::class, [
            'filterMultiSelect' => Expected::never(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value for user filter must be an array');

        $filter = new SystemUserFilter();
        $filter->apply($parameter, $query);
    }
}
