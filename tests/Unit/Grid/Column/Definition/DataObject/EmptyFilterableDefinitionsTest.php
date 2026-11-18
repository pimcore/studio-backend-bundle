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
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\AdvancedManyToManyObjectRelationDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\AdvancedManyToManyRelationDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\BlockDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\GeopointDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\HotSpotImageDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\ImageDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\ImageGalleryDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\ManyToManyObjectRelationDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\ManyToManyRelationDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\ManyToOneRelationDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\PasswordDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\QuantityValueDefinition;

/**
 * Fields without a value filter can still be searched for "no value" / "any value".
 *
 * @internal
 */
final class EmptyFilterableDefinitionsTest extends Unit
{
    /**
     * @dataProvider filterableProvider
     */
    public function testDefinitionIsFilterable(ColumnDefinitionInterface $definition): void
    {
        $this->assertTrue($definition->isFilterable());
    }

    public function filterableProvider(): array
    {
        return [
            'many to one relation' => [new ManyToOneRelationDefinition()],
            'many to many relation' => [new ManyToManyRelationDefinition()],
            'many to many object relation' => [new ManyToManyObjectRelationDefinition()],
            'advanced many to many relation' => [new AdvancedManyToManyRelationDefinition()],
            'advanced many to many object relation' => [new AdvancedManyToManyObjectRelationDefinition()],
            'quantity value' => [new QuantityValueDefinition()],
            'image' => [new ImageDefinition()],
            'image gallery' => [new ImageGalleryDefinition()],
            'geopoint' => [new GeopointDefinition()],
        ];
    }

    /**
     * @dataProvider notFilterableProvider
     */
    public function testDefinitionStaysNotFilterable(ColumnDefinitionInterface $definition): void
    {
        $this->assertFalse($definition->isFilterable());
    }

    public function notFilterableProvider(): array
    {
        return [
            'block' => [new BlockDefinition()],
            'password' => [new PasswordDefinition()],
            'hotspot image' => [new HotSpotImageDefinition()],
        ];
    }
}
