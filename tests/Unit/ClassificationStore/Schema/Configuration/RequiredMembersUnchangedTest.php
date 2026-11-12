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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Schema\Configuration;

use Codeception\Test\Unit;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\CollectionLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\ImportReport;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreTreeNode;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyGroupRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\SubGroupLayout;
use ReflectionClass;

/**
 * Every member this feature adds is optional and nullable, so a client built against the previous
 * OpenAPI document keeps deserializing every one of these responses. The `required` list is what a
 * generated client turns into a non-nullable field, so each one is asserted as a whole rather than
 * only for the absence of a new name.
 *
 * @internal
 */
final class RequiredMembersUnchangedTest extends Unit
{
    /**
     * @dataProvider schemaProvider
     *
     * @param string[] $required
     */
    public function testTheRequiredListOfATouchedSchemaIsUnchanged(string $schema, array $required): void
    {
        $attributes = (new ReflectionClass($schema))->getAttributes(Schema::class);

        $this->assertSame($required, $attributes[0]->getArguments()['required']);
    }

    /**
     * @return array<string, array{0: string, 1: string[]}>
     */
    public static function schemaProvider(): array
    {
        return [
            StoreTreeNode::class => [StoreTreeNode::class, ['id', 'name', 'description']],
            StoreDetail::class => [StoreDetail::class, ['id', 'name', 'description']],
            KeyLayout::class => [KeyLayout::class, ['id', 'name', 'description', 'definition']],
            ImportReport::class => [ImportReport::class, [
                'created', 'updated', 'adopted', 'matchedUnchanged', 'targetOnly',
                'createdEntries', 'updatedEntries', 'adoptedEntries', 'targetOnlyEntries', 'conflicts',
            ]],
            KeyGroupRelation::class => [KeyGroupRelation::class, [
                'keyId', 'groupId', 'keyName', 'groupName', 'keyDescription', 'groupDescription',
            ]],
            KeyDetail::class => [KeyDetail::class, [
                'id', 'name', 'storeId', 'type', 'enabled', 'description',
                'definition', 'creationDate', 'modificationDate',
            ]],
            KeyCreate::class => [KeyCreate::class, ['name', 'storeId']],
            KeyUpdate::class => [KeyUpdate::class, ['name', 'type', 'definition']],
            CollectionDetail::class => [CollectionDetail::class, [
                'id', 'name', 'storeId', 'description', 'creationDate', 'modificationDate',
            ]],
            GroupDetail::class => [GroupDetail::class, [
                'id', 'name', 'storeId', 'description', 'creationDate', 'modificationDate',
            ]],
            SubGroupDetail::class => [SubGroupDetail::class, [
                'id', 'name', 'storeId', 'description', 'creationDate', 'modificationDate',
            ]],
            CollectionUpdate::class => [CollectionUpdate::class, ['name', 'description']],
            GroupUpdate::class => [GroupUpdate::class, ['name', 'description']],
            SubGroupUpdate::class => [SubGroupUpdate::class, ['name', 'description']],
            CollectionCreate::class => [CollectionCreate::class, ['name', 'storeId']],
            GroupCreate::class => [GroupCreate::class, ['name', 'storeId']],
            SubGroupCreate::class => [SubGroupCreate::class, ['name', 'storeId']],
        ];
    }

    /**
     * Nothing beside the declaration members joined the key layout, so no response member a
     * client does not expect appears on it either.
     */
    public function testTheKeyLayoutGainedOnlyTheDeclarationMembers(): void
    {
        $this->assertSame(
            [
                'id', 'name', 'description', 'definition', 'title', 'subGroup', 'localized',
                'variants', 'multiValue', 'maximum', 'defaultVariant', 'defaultVariantLabel',
            ],
            $this->getConstructorParameterNames(KeyLayout::class)
        );
    }

    /**
     * The other surfaces that describe these entities publish no pairs: the layouts the object editor
     * reads, the store-wide key picker and the assignment grid.
     *
     * @dataProvider pairlessSchemaProvider
     */
    public function testNoOtherSurfaceGainedTheCustomMetadataMember(string $schema): void
    {
        $this->assertNotContains('customMetadata', $this->getConstructorParameterNames($schema));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function pairlessSchemaProvider(): array
    {
        return [
            KeyLayout::class => [KeyLayout::class],
            GroupLayout::class => [GroupLayout::class],
            SubGroupLayout::class => [SubGroupLayout::class],
            CollectionLayout::class => [CollectionLayout::class],
            KeyGroupRelation::class => [KeyGroupRelation::class],
            KeyGroupRelationDetail::class => [KeyGroupRelationDetail::class],
            StoreDetail::class => [StoreDetail::class],
            StoreTreeNode::class => [StoreTreeNode::class],
        ];
    }

    /**
     * No `warnings` array joined the key detail, only the optional `customMetadata` and the three
     * resolved members `options`, `optionsUnavailableReason` and `units`: a declaration a store's
     * engine cannot serve is accepted and nothing is said about it in the body.
     */
    public function testTheKeyDetailGainedNoResponseMember(): void
    {
        $this->assertSame(
            [
                'id', 'name', 'storeId', 'type', 'enabled', 'description', 'definition',
                'creationDate', 'modificationDate', 'title', 'identifier', 'localized',
                'customMetadata', 'options', 'optionsUnavailableReason', 'units',
            ],
            $this->getConstructorParameterNames(KeyDetail::class)
        );
    }

    /**
     * @return string[]
     */
    private function getConstructorParameterNames(string $schema): array
    {
        $names = [];
        foreach ((new ReflectionClass($schema))->getConstructor()->getParameters() as $parameter) {
            $names[] = $parameter->getName();
        }

        return $names;
    }
}
