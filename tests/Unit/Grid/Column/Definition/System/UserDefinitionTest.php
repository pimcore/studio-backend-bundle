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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Column\Definition\System;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\System\UserDefinition;

/**
 * @internal
 */
final class UserDefinitionTest extends Unit
{
    private UserDefinition $definition;

    protected function setUp(): void
    {
        $this->definition = new UserDefinition();
    }

    public function testGetType(): void
    {
        $this->assertSame('system.user', $this->definition->getType());
    }

    public function testGetFrontendType(): void
    {
        $this->assertSame('input', $this->definition->getFrontendType());
    }

    public function testIsSortable(): void
    {
        $this->assertTrue($this->definition->isSortable());
    }

    public function testIsFilterable(): void
    {
        $this->assertTrue($this->definition->isFilterable());
    }

    public function testIsExportable(): void
    {
        $this->assertTrue($this->definition->isExportable());
    }

    public function testGetConfigReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->definition->getConfig('anything'));
    }
}
