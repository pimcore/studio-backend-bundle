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
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\SortFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter as SortFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilterParameterInterface;

/**
 * @internal
 */
final class SortFilterTest extends Unit
{
    public function testIfParameterIsNotInstanceOfSortFilterParameterInterface(): void
    {
        $sortFilter = new SortFilter();
        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'orderByField' => Expected::never(),
        ]);

        $sortFilter->apply('test', $query);
    }

    public function testSortDirectionWithDesc(): void
    {
        $sortFilter = new SortFilter();
        $sortFilterParam = new SortFilterParameter('key', 'desc');
        $parameter = $this->makeEmpty(SortFilterParameterInterface::class, [
            'getSortFilter' => function () use ($sortFilterParam) {
                return $sortFilterParam;
            },
            'getSortFilters' => function () use ($sortFilterParam) {
                return [$sortFilterParam];
            },
        ]);

        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'orderByField' => Expected::once(function ($key, $direction) {
                $this->assertSame('key', $key);
                $this->assertSame(SortDirection::DESC, $direction);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $sortFilter->apply($parameter, $query);
    }

    public function testSortDirectionWithDefaultValue(): void
    {
        $sortFilter = new SortFilter();
        $sortFilterParam = new SortFilterParameter();
        $parameter = $this->makeEmpty(SortFilterParameterInterface::class, [
            'getSortFilter' => function () use ($sortFilterParam) {
                return $sortFilterParam;
            },
            'getSortFilters' => function () use ($sortFilterParam) {
                return [$sortFilterParam];
            },
        ]);

        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'orderByField' => Expected::once(function ($key, $direction) {
                $this->assertSame('id', $key);
                $this->assertSame(SortDirection::ASC, $direction);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $sortFilter->apply($parameter, $query);
    }

    public function testMultipleSortFilters(): void
    {
        $sortFilter = new SortFilter();
        $primarySort = new SortFilterParameter('name', 'asc');
        $secondarySort = new SortFilterParameter('id', 'desc');
        $parameter = $this->makeEmpty(SortFilterParameterInterface::class, [
            'getSortFilter' => function () use ($primarySort) {
                return $primarySort;
            },
            'getSortFilters' => function () use ($primarySort, $secondarySort) {
                return [$primarySort, $secondarySort];
            },
        ]);

        $callCount = 0;
        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'orderByField' => function ($key, $direction) use (&$callCount) {
                if ($callCount === 0) {
                    $this->assertSame('name', $key);
                    $this->assertSame(SortDirection::ASC, $direction);
                } elseif ($callCount === 1) {
                    $this->assertSame('id', $key);
                    $this->assertSame(SortDirection::DESC, $direction);
                }
                $callCount++;

                return $this->makeEmpty(AssetQueryInterface::class);
            },
        ]);

        $sortFilter->apply($parameter, $query);
        $this->assertSame(2, $callCount);
    }

    public function testSortByClassnameColumnKeyMapsToIndexField(): void
    {
        $sortFilter = new SortFilter();
        $sortFilterParam = new SortFilterParameter('classname', 'asc');
        $parameter = $this->makeEmpty(SortFilterParameterInterface::class, [
            'getSortFilter' => function () use ($sortFilterParam) {
                return $sortFilterParam;
            },
            'getSortFilters' => function () use ($sortFilterParam) {
                return [$sortFilterParam];
            },
        ]);

        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'orderByField' => Expected::once(function ($key, $direction) {
                $this->assertSame('className', $key);
                $this->assertSame(SortDirection::ASC, $direction);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $sortFilter->apply($parameter, $query);
    }
}
