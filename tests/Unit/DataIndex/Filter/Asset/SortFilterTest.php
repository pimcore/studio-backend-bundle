<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\Asset;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\SortFilter;
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
            'orderByField' => Expected::never()
        ]);

        $sortFilter->apply("test", $query);
    }

    public function testSortDirectionWithDesc(): void
    {
        $sortFilter = new SortFilter();
        $parameter = $this->makeEmpty(SortFilterParameterInterface::class, [
            'getSortFilter' => function () {
                return new SortFilterParameter("key", "desc");
            }
        ]);

        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'orderByField' => function ($key, $direction) {
                $this->assertSame("key", $key);
                $this->assertSame(SortDirection::DESC, $direction);

                return $this->makeEmpty(AssetQueryInterface::class);
            }
        ]);

        $sortFilter->apply($parameter, $query);
    }

    public function testSortDirectionWithDefaultValue(): void
    {
        $sortFilter = new SortFilter();
        $parameter = $this->makeEmpty(SortFilterParameterInterface::class, [
            'getSortFilter' => function () {
                return new SortFilterParameter();
            }
        ]);

        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'orderByField' => function ($key, $direction) {
                $this->assertSame("id", $key);
                $this->assertSame(SortDirection::ASC, $direction);

                return $this->makeEmpty(AssetQueryInterface::class);
            }
        ]);

        $sortFilter->apply($parameter, $query);
    }

}