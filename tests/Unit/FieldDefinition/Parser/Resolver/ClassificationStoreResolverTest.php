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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver\ClassificationStoreResolver;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * Regression test for platform-version#577: a classification store key is addressed as
 * "<storeFieldName>.<groupId>.<keyId>" and resolved with the owner context the options provider expects.
 *
 * @internal
 */
final class ClassificationStoreResolverTest extends Unit
{
    private const int STORE_ID = 1;

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
     * The dot notation addresses the group before the key and the lookup takes them in that same
     * order, so passing the pair the other way round would silently look up a mirrored relation.
     * Nothing else in this test asserts the arguments, so the order is pinned here.
     *
     * @throws Exception
     */
    public function testResolveLooksUpTheRelationByGroupThenKey(): void
    {
        $arguments = [];

        $keyGroupRelation = new KeyGroupRelation();
        $keyGroupRelation->setKeyId(6);
        $keyGroupRelation->setGroupId(2);
        $keyGroupRelation->setType('select');
        $keyGroupRelation->setDefinition('{"fieldtype":"select"}');

        $resolver = new ClassificationStoreResolver(
            $this->createGroupConfigRepository(self::STORE_ID),
            $this->makeEmpty(KeyRelationResolverInterface::class, [
                'getByGroupAndKey' => static function (int $groupId, int $keyId) use (
                    &$arguments,
                    $keyGroupRelation
                ): KeyGroupRelation {
                    $arguments = [$groupId, $keyId];

                    return $keyGroupRelation;
                },
            ]),
            $this->makeEmpty(ServiceResolverInterface::class, [
                'getFieldDefinitionFromJson' => new Select(),
            ]),
        );
        $resolver->setFieldDefinitions($this->getFieldDefinitions());

        $resolver->resolve(['attributes', '2', '6']);

        $this->assertSame([2, 6], $arguments);
    }

    /**
     * @throws Exception
     */
    public function testResolveThrowsExceptionForUnknownKeyGroupRelation(): void
    {
        $resolver = new ClassificationStoreResolver(
            $this->createGroupConfigRepository(self::STORE_ID),
            $this->makeEmpty(KeyRelationResolverInterface::class, [
                'getByGroupAndKey' => static function (): never {
                    throw new NotFoundException('KeyGroupRelation', 6);
                },
            ]),
            $this->makeEmpty(ServiceResolverInterface::class),
        );
        $resolver->setFieldDefinitions($this->getFieldDefinitions());

        try {
            $resolver->resolve(['attributes', '2', '6']);
            $this->fail('Expected a ParseException');
        } catch (ParseException $exception) {
            $this->assertSame(
                'Key "6" does not exist in classification store group "2"',
                $exception->getMessage()
            );
            $this->assertInstanceOf(NotFoundException::class, $exception->getPrevious());
        }
    }

    /**
     * @throws Exception
     */
    public function testResolveThrowsExceptionForGroupOfAnotherStore(): void
    {
        $resolver = $this->createResolver(groupStoreId: 2);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Group "2" does not belong to the store of classification store "attributes"');

        $resolver->resolve(['attributes', '2', '6']);
    }

    /**
     * @throws Exception
     */
    public function testResolveThrowsExceptionForUnknownGroup(): void
    {
        $resolver = new ClassificationStoreResolver(
            $this->makeEmpty(GroupConfigRepositoryInterface::class, [
                'getById' => static function (): never {
                    throw new NotFoundException('group', 2);
                },
            ]),
            $this->makeEmpty(KeyRelationResolverInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class),
        );
        $resolver->setFieldDefinitions($this->getFieldDefinitions());

        try {
            $resolver->resolve(['attributes', '2', '6']);
            $this->fail('Expected a ParseException');
        } catch (ParseException $exception) {
            $this->assertSame('Classification store group "2" does not exist', $exception->getMessage());
            $this->assertInstanceOf(NotFoundException::class, $exception->getPrevious());
        }
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
    private function createResolver(
        ?Data $keyDefinition = null,
        int $groupStoreId = self::STORE_ID
    ): ClassificationStoreResolver {
        $keyGroupRelation = new KeyGroupRelation();
        $keyGroupRelation->setKeyId(6);
        $keyGroupRelation->setGroupId(2);
        $keyGroupRelation->setType('select');
        $keyGroupRelation->setDefinition('{"fieldtype":"select"}');

        $resolver = new ClassificationStoreResolver(
            $this->createGroupConfigRepository($groupStoreId),
            $this->makeEmpty(KeyRelationResolverInterface::class, [
                'getByGroupAndKey' => $keyGroupRelation,
            ]),
            $this->makeEmpty(ServiceResolverInterface::class, [
                'getFieldDefinitionFromJson' => $keyDefinition ?? new Select(),
            ]),
        );
        $resolver->setFieldDefinitions($this->getFieldDefinitions());

        return $resolver;
    }

    /**
     * @throws Exception
     */
    private function createGroupConfigRepository(int $groupStoreId): GroupConfigRepositoryInterface
    {
        $group = new GroupConfig();
        $group->setId(2);
        $group->setStoreId($groupStoreId);

        return $this->makeEmpty(GroupConfigRepositoryInterface::class, ['getById' => $group]);
    }

    /**
     * @return array<string, Data>
     */
    private function getFieldDefinitions(): array
    {
        $classificationStore = new Classificationstore();
        $classificationStore->setName('attributes');
        $classificationStore->setStoreId(self::STORE_ID);

        $input = new Input();
        $input->setName('name');

        return [
            'attributes' => $classificationStore,
            'name' => $input,
        ];
    }
}
