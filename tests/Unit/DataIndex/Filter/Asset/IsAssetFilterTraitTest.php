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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\Asset;

use DateTime;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\IsAssetFilterTrait;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 * @covers \Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\IsAssetFilterTrait
 */
final class IsAssetFilterTraitTest extends TestCase
{
    public function testValidateParameterTypeNullIfWrongInterface(): void
    {
        $myTestClass = new MyTestClass();

        $this->assertNull(
            $myTestClass->validateParameterType(new DateTime())
        );
    }

    public function testValidateParameterType(): void
    {
        $myTestClass = new MyTestClass();
        $columnFiltersParameterInterfaceMock = $this->createMock(ColumnFiltersParameterInterface::class);
        $this->assertSame(
            $columnFiltersParameterInterfaceMock,
            $myTestClass->validateParameterType($columnFiltersParameterInterfaceMock)
        );
    }

    public function testValidateQueryTypeNullIfWrongInterface(): void
    {
        $myTestClass = new MyTestClass();

        $this->assertNull(
            $myTestClass->validateQueryType(new DateTime())
        );
    }

    public function testValidateQueryType(): void
    {
        $myTestClass = new MyTestClass();
        $columnFiltersParameterInterfaceMock = $this->createMock(AssetQueryInterface::class);
        $this->assertSame(
            $columnFiltersParameterInterfaceMock,
            $myTestClass->validateQueryType($columnFiltersParameterInterfaceMock)
        );
    }
}

final class MyTestClass
{
    use IsAssetFilterTrait;
}
