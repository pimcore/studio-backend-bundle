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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\FieldDefinition\Parser\Resolver;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver\ClassificationStoreResolver;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * Regression test for platform-version#577: a classification store key is addressed as
 * "<storeFieldName>.<groupId>.<keyId>" and resolved with the owner context the options provider expects.
 *
 * @internal
 */
final class ClassificationStoreResolverTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testCanResolveClassificationStoreKey(): void
    {
        $resolver = $this->createResolver();

        $this->assertTrue($resolver->canResolve(['attributes', '2', '6']));
        $this->assertTrue($resolver->canResolve(['attributes', '2', 'en', '6']));
    }

    /**
     * @throws Exception
     */
    public function testCanNotResolveOtherDotNotations(): void
    {
        $resolver = $this->createResolver();

        $this->assertFalse($resolver->canResolve(['6']));
        $this->assertFalse($resolver->canResolve(['attributes', '2']));
        $this->assertFalse($resolver->canResolve(['attributes', 'group', '6']));
        $this->assertFalse($resolver->canResolve(['attributes', '2', '6', 'en']));
        $this->assertFalse($resolver->canResolve(['attributes', '2', 'en', 'default', '6']));
        $this->assertFalse($resolver->canResolve(['name', '2', '6']));
        $this->assertFalse($resolver->canResolve(['unknown', '2', '6']));
    }

    /**
     * @throws Exception
     */
    public function testResolveClassificationStoreKey(): void
    {
        $keyDefinition = new Select();
        $keyDefinition->setName('fittingType');

        $resolver = $this->createResolver($keyDefinition);

        $wrapper = $resolver->resolve(['attributes', '2', '6']);

        $this->assertSame($keyDefinition, $wrapper->getFieldDefinition());
        $this->assertSame('classificationstore', $wrapper->getContainerType());
        $this->assertSame('attributes', $wrapper->getFieldname());
        $this->assertSame(
            [
                'ownerType' => 'classificationstore',
                'ownerName' => 'attributes',
                'keyId' => 6,
                'groupId' => 2,
                'keyDefinition' => $keyDefinition,
            ],
            $wrapper->getOptionsProviderContext()
        );
    }

    /**
     * @throws Exception
     */
    public function testResolveLocalizedClassificationStoreKey(): void
    {
        $keyDefinition = new Select();
        $keyDefinition->setName('fittingType');

        $resolver = $this->createResolver($keyDefinition);

        $wrapper = $resolver->resolve(['attributes', '2', 'en', '6']);

        $this->assertSame($keyDefinition, $wrapper->getFieldDefinition());
        $this->assertSame(6, $wrapper->getOptionsProviderContext()['keyId']);
        $this->assertSame(2, $wrapper->getOptionsProviderContext()['groupId']);
    }

    /**
     * @throws Exception
     */
    public function testResolveThrowsExceptionForUnknownKeyGroupRelation(): void
    {
        $resolver = new ClassificationStoreResolver(
            $this->makeEmpty(KeyGroupRelationRepositoryInterface::class, [
                'getByKeyGroupId' => static function (): never {
                    throw new NotFoundException('KeyGroupRelation', 6);
                },
            ]),
            $this->makeEmpty(ServiceResolverInterface::class),
        );
        $resolver->setFieldDefinitions($this->getFieldDefinitions());

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Key "6" does not exist in classification store group "2"');

        $resolver->resolve(['attributes', '2', '6']);
    }

    /**
     * @throws Exception
     */
    public function testResolveThrowsExceptionForNonClassificationStoreField(): void
    {
        $resolver = $this->createResolver();

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Field "name" is not a classification store');

        $resolver->resolve(['name', '2', '6']);
    }

    /**
     * @throws Exception
     */
    private function createResolver(?Data $keyDefinition = null): ClassificationStoreResolver
    {
        $keyGroupRelation = new KeyGroupRelation();
        $keyGroupRelation->setKeyId(6);
        $keyGroupRelation->setGroupId(2);

        $resolver = new ClassificationStoreResolver(
            $this->makeEmpty(KeyGroupRelationRepositoryInterface::class, [
                'getByKeyGroupId' => $keyGroupRelation,
            ]),
            $this->makeEmpty(ServiceResolverInterface::class, [
                'getFieldDefinitionFromKeyConfig' => $keyDefinition ?? new Select(),
            ]),
        );
        $resolver->setFieldDefinitions($this->getFieldDefinitions());

        return $resolver;
    }

    /**
     * @return array<string, Data>
     */
    private function getFieldDefinitions(): array
    {
        $classificationStore = new Classificationstore();
        $classificationStore->setName('attributes');

        $input = new Input();
        $input->setName('name');

        return [
            'attributes' => $classificationStore,
            'name' => $input,
        ];
    }
}
