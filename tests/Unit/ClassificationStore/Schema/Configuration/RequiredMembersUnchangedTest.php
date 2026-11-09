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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreTreeNode;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyGroupRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
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
            KeyGroupRelation::class => [KeyGroupRelation::class, [
                'keyId', 'groupId', 'keyName', 'groupName', 'keyDescription', 'groupDescription',
            ]],
            KeyDetail::class => [KeyDetail::class, [
                'id', 'name', 'storeId', 'type', 'enabled', 'description',
                'definition', 'creationDate', 'modificationDate',
            ]],
            KeyCreate::class => [KeyCreate::class, ['name', 'storeId']],
            KeyUpdate::class => [KeyUpdate::class, ['name', 'type', 'definition']],
        ];
    }

    /**
     * Nothing beside the two members of this feature joined the group layout, so no response member
     * a client does not expect appears on it either.
     */
    public function testTheKeyLayoutGainedOnlyTheTwoDeclarationMembers(): void
    {
        $this->assertSame(
            [
                'id', 'name', 'description', 'definition', 'title', 'subGroup', 'localized',
                'variants', 'multiValue',
            ],
            $this->getConstructorParameterNames(KeyLayout::class)
        );
    }

    /**
     * No `warnings` array and no other new member joined the key schemas: a declaration a store's
     * engine cannot serve is accepted and nothing is said about it in the body.
     */
    public function testTheKeyDetailGainedNoResponseMember(): void
    {
        $this->assertSame(
            [
                'id', 'name', 'storeId', 'type', 'enabled', 'description', 'definition',
                'creationDate', 'modificationDate', 'title', 'identifier', 'localized',
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
