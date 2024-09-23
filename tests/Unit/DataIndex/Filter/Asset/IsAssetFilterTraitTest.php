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

use Codeception\Test\Unit;
use DateTime;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\IsAssetFilterTrait;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
final class IsAssetFilterTraitTest extends Unit
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
        $columnFiltersParameterInterfaceMock = $this->makeEmpty(ColumnFiltersParameterInterface::class);
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
        $columnFiltersParameterInterfaceMock = $this->makeEmpty(AssetQueryInterface::class);
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
