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
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\QuantityValueDefinition;

/**
 * @internal
 */
final class QuantityValueDefinitionTest extends Unit
{
    private QuantityValueDefinition $definition;

    protected function setUp(): void
    {
        $this->definition = new QuantityValueDefinition();
    }

    public function testGetType(): void
    {
        $this->assertSame('data-object.quantityValue', $this->definition->getType());
    }

    public function testGetFrontendType(): void
    {
        $this->assertSame('quantityValue', $this->definition->getFrontendType());
    }

    public function testIsSortable(): void
    {
        $this->assertTrue($this->definition->isSortable());
    }
}
