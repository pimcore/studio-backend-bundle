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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Column\Definition\DataObject;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\FieldCollectionDefinition;

/**
 * @internal
 */
final class FieldCollectionDefinitionTest extends Unit
{
    private FieldCollectionDefinition $definition;

    protected function setUp(): void
    {
        $this->definition = new FieldCollectionDefinition();
    }

    public function testGetType(): void
    {
        $this->assertSame('data-object.fieldcollections', $this->definition->getType());
    }

    public function testGetFrontendType(): void
    {
        $this->assertSame('fieldcollections', $this->definition->getFrontendType());
    }

    public function testIsNotSortable(): void
    {
        $this->assertFalse($this->definition->isSortable());
    }

    public function testIsNotFilterable(): void
    {
        $this->assertFalse($this->definition->isFilterable());
    }

    public function testIsNotExportable(): void
    {
        $this->assertFalse($this->definition->isExportable());
    }

    public function testGetConfigReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->definition->getConfig('anything'));
    }
}
