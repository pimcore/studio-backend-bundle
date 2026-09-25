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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FileSizeFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
final class FileSizeFilterTest extends Unit
{
    use ColumnFilterMockTrait;

    private const KB = 1024;

    private const MB = 1024 * 1024;

    private const GB = 1024 * 1024 * 1024;

    public function testThrowsWhenFilterValueIsNotAnArray(): void
    {
        $filter = new FileSizeFilter();
        $parameters = $this->getColumnFilterMock('fileSize', 'system.fileSize', 123);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This filter requires a setting value');
        $filter->apply($parameters, $this->makeEmpty(AssetQueryInterface::class));
    }

    public function testThrowsWhenUnitIsMissing(): void
    {
        $filter = new FileSizeFilter();
        $parameters = $this->getColumnFilterMock('fileSize', 'system.fileSize', ['setting' => 'is', 'is' => 1]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File size filter requires a valid unit (KB, MB or GB)');
        $filter->apply($parameters, $this->makeEmpty(AssetQueryInterface::class));
    }

    public function testThrowsWhenUnitIsInvalid(): void
    {
        $filter = new FileSizeFilter();
        $parameters = $this->getColumnFilterMock(
            'fileSize',
            'system.fileSize',
            ['setting' => 'is', 'is' => 1, 'unit' => 'TB']
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File size filter requires a valid unit (KB, MB or GB)');
        $filter->apply($parameters, $this->makeEmpty(AssetQueryInterface::class));
    }

    public function testIsProducesAnInclusiveOneUnitBand(): void
    {
        $filter = new FileSizeFilter();
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterNumberRange' => Expected::once(function ($field, $min, $max) {
                $this->assertSame('fileSize', $field);
                // Exclusive gt/lt widened by one byte -> inclusive [1 MB, 2 MB - 1].
                $this->assertSame(self::MB - 1, $min);
                $this->assertSame(self::MB + self::MB, $max);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $parameters = $this->getColumnFilterMock(
            'fileSize',
            'system.fileSize',
            ['setting' => 'is', 'is' => 1, 'unit' => 'MB']
        );

        $filter->apply($parameters, $queryMock);
    }

    public function testLessConvertsKilobytesToBytes(): void
    {
        $filter = new FileSizeFilter();
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterNumberRange' => Expected::once(function ($field, $min, $max) {
                $this->assertSame('fileSize', $field);
                $this->assertNull($min);
                $this->assertSame(500 * self::KB, $max);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $parameters = $this->getColumnFilterMock(
            'fileSize',
            'system.fileSize',
            ['setting' => 'less', 'to' => 500, 'unit' => 'KB']
        );

        $filter->apply($parameters, $queryMock);
    }

    public function testMoreConvertsGigabytesToBytes(): void
    {
        $filter = new FileSizeFilter();
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterNumberRange' => Expected::once(function ($field, $min, $max) {
                $this->assertSame('fileSize', $field);
                $this->assertSame(2 * self::GB, $min);
                $this->assertNull($max);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $parameters = $this->getColumnFilterMock(
            'fileSize',
            'system.fileSize',
            ['setting' => 'more', 'from' => 2, 'unit' => 'GB']
        );

        $filter->apply($parameters, $queryMock);
    }

    public function testBetweenConvertsBothBounds(): void
    {
        $filter = new FileSizeFilter();
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterNumberRange' => Expected::once(function ($field, $min, $max) {
                $this->assertSame('fileSize', $field);
                $this->assertSame(self::MB, $min);
                $this->assertSame(2 * self::MB, $max);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $parameters = $this->getColumnFilterMock(
            'fileSize',
            'system.fileSize',
            ['setting' => 'between', 'from' => 1, 'to' => 2, 'unit' => 'MB']
        );

        $filter->apply($parameters, $queryMock);
    }

    public function testBetweenToleratesASingleBound(): void
    {
        $filter = new FileSizeFilter();
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterNumberRange' => Expected::once(function ($field, $min, $max) {
                $this->assertSame('fileSize', $field);
                $this->assertSame(self::MB, $min);
                $this->assertNull($max);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $parameters = $this->getColumnFilterMock(
            'fileSize',
            'system.fileSize',
            ['setting' => 'between', 'from' => 1, 'unit' => 'MB']
        );

        $filter->apply($parameters, $queryMock);
    }

    public function testThrowsWhenSettingHasNoUsableValue(): void
    {
        $filter = new FileSizeFilter();
        $parameters = $this->getColumnFilterMock(
            'fileSize',
            'system.fileSize',
            ['setting' => 'is', 'unit' => 'MB']
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to apply file size filter, no correct setting given');
        $filter->apply($parameters, $this->makeEmpty(AssetQueryInterface::class));
    }
}
