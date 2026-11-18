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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\RelationVisibleFieldsConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\RelationVisibleFieldDefinitionFactory;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;

/**
 * @internal
 */
final class RelationVisibleFieldDefinitionFactoryTest extends Unit
{
    public function testBuildsAManyToManyRelationWithTheUnsavedAllowedTypes(): void
    {
        $fieldDefinition = (new RelationVisibleFieldDefinitionFactory())->create(
            new RelationVisibleFieldsConfiguration(
                fieldtype: 'manyToManyRelation',
                objectsAllowed: true,
                classes: ['Product', 'Category', 'Product', ''],
                assetsAllowed: true,
                assetTypes: ['image'],
                documentsAllowed: true,
                documentTypes: ['page'],
            )
        );

        $this->assertSame(ManyToManyRelation::class, $fieldDefinition::class);
        $this->assertTrue($fieldDefinition->getObjectsAllowed());
        $this->assertSame([['classes' => 'Product'], ['classes' => 'Category']], $fieldDefinition->getClasses());
        $this->assertTrue($fieldDefinition->getAssetsAllowed());
        $this->assertSame([['assetTypes' => 'image']], $fieldDefinition->getAssetTypes());
        $this->assertTrue($fieldDefinition->getDocumentsAllowed());
        $this->assertSame([['documentTypes' => 'page']], $fieldDefinition->getDocumentTypes());
        $this->assertNull($fieldDefinition->getVisibleFields());
    }

    public function testBuildsAnAdvancedManyToManyRelation(): void
    {
        $fieldDefinition = (new RelationVisibleFieldDefinitionFactory())->create(
            new RelationVisibleFieldsConfiguration(fieldtype: 'advancedManyToManyRelation', assetsAllowed: true)
        );

        $this->assertInstanceOf(AdvancedManyToManyRelation::class, $fieldDefinition);
        $this->assertFalse($fieldDefinition->getObjectsAllowed());
        $this->assertTrue($fieldDefinition->getAssetsAllowed());
        $this->assertFalse($fieldDefinition->getDocumentsAllowed());
    }

    public function testRejectsTypesWithoutVisibleFieldSupport(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RelationVisibleFieldDefinitionFactory())->create(
            new RelationVisibleFieldsConfiguration(fieldtype: 'manyToManyObjectRelation', objectsAllowed: true)
        );
    }
}
