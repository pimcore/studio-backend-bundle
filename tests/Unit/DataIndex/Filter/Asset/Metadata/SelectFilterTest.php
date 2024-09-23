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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\Asset\Metadata;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\FilterType;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\SelectFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
final class SelectFilterTest extends Unit
{
    use ColumnFilterMockTrait;
    public function testIsExceptionIsThrownWhenFilterIsNotAString(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::never()
        ]);

        $columnFilterMock = $this->getColumnFilterMock('key', "type", 123);

        $stringFilter = new SelectFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Filter value for select must be a string");
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplySelectFilter(): void
    {
        $columnFilterMock = $this->getColumnFilterMock('key', "type", "value");

        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::once(function ($key, $type, $value) {
                $this->assertSame("key", $key);
                $this->assertSame(FilterType::SELECT->value, $type);
                $this->assertSame("value", $value);

                return $this->makeEmpty(AssetQueryInterface::class);
            })
        ]);

        $textAreaFilter = new SelectFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }
}