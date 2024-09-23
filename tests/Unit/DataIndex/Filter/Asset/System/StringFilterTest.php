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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\Asset\System;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\System\StringFilter;
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
}
