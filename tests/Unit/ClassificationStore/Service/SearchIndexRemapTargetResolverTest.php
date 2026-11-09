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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTargetResolver;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldCollectionDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectBrickDefinition;

/**
 * Which search indices a store reaches: one per data object class carrying a field of it, each at
 * most once, and none at all while no class does.
 *
 * A class carries a classification store field at the top level or inside a container - localized
 * fields, an object brick, a field collection - and the value rows and the index mapping are the
 * same either way, so a store reached only through a container is the same remap as one reached
 * directly. Asking the class for its own fields alone would answer with neither container, which is
 * the mistake the maintenance cleanup of this epic had already made once; the walk used here is the
 * one the engine routing and `classSaved()` resolve the very same fields with.
 *
 * The ids are the strings a class carries them as. A class id made of digits is a legal id - and it
 * is also an integer array key, so collecting the classes in an array keyed by id and reading the
 * keys back would answer with integers, which the queue refuses: the save that asked for the remap
 * would fail and nothing would be queued at all.
 *
 * @internal
 */
final class SearchIndexRemapTargetResolverTest extends Unit
{
    private const int STORE_ID = 1;

    private const int OTHER_STORE_ID = 2;

    private const string FIELD_NAME = 'properties';

    private const string BRICK_KEY = 'Shared';

    private const string COLLECTION_KEY = 'Shared';

    public function _after(): void
    {
        RuntimeCache::getInstance()->offsetUnset('objectbrick_' . self::BRICK_KEY);
        RuntimeCache::getInstance()->offsetUnset('fieldcollection_' . self::COLLECTION_KEY);
    }

    /**
     * A class id is a string that may be made of digits alone, which is what the installations that
     * never moved to named class ids carry.
     */
    public function testANumericClassIdIsResolvedAsTheStringTheClassCarriesIt(): void
    {
        $resolver = $this->createResolver([
            $this->createClassCarryingStore('5', self::STORE_ID),
        ]);

        $this->assertSame(['5'], $resolver->resolveForStore(self::STORE_ID));
    }

    /**
     * And a numeric id beside a named one keeps being its own answer rather than the position it
     * happened to be walked in.
     */
    public function testNumericAndNamedClassIdsAreResolvedSideBySide(): void
    {
        $resolver = $this->createResolver([
            $this->createClassCarryingStore('5', self::STORE_ID),
            $this->createClassCarryingStore('CAR', self::STORE_ID),
        ]);

        $this->assertSame(['5', 'CAR'], $resolver->resolveForStore(self::STORE_ID));
    }

    public function testAClassCarryingAnotherStoreIsNotATargetOfThisOne(): void
    {
        $resolver = $this->createResolver([
            $this->createClassCarryingStore('CAR', self::OTHER_STORE_ID),
        ]);

        $this->assertSame([], $resolver->resolveForStore(self::STORE_ID));
    }

    /**
     * A store is one index per class, so a class carrying two fields of the same store is still
     * remapped once.
     */
    public function testAClassCarryingTheStoreTwiceIsResolvedOnce(): void
    {
        $class = $this->createClassCarryingStore('CAR', self::STORE_ID);
        $class->setFieldDefinitions(
            $class->getFieldDefinitions() + [
                'more' => $this->createStoreField('more', self::STORE_ID),
            ]
        );

        $this->assertSame(['CAR'], $this->createResolver([$class])->resolveForStore(self::STORE_ID));
    }

    /**
     * A store reached only through a container is reached by the same keys as one at the top level,
     * so it is the same remap: a class is a target however deep its field of the store sits.
     *
     * @dataProvider containerProvider
     */
    public function testAStoreReachedOnlyThroughAContainerIsStillATarget(string $container): void
    {
        $resolver = $this->createResolver([
            $this->createClassCarryingStoreIn($container, 'CAR', self::STORE_ID),
        ]);

        $this->assertSame(['CAR'], $resolver->resolveForStore(self::STORE_ID));
    }

    /**
     * And the container is no excuse for answering with a store the class does not carry either.
     *
     * @dataProvider containerProvider
     */
    public function testAContainerCarryingAnotherStoreIsNotATargetOfThisOne(string $container): void
    {
        $resolver = $this->createResolver([
            $this->createClassCarryingStoreIn($container, 'CAR', self::OTHER_STORE_ID),
        ]);

        $this->assertSame([], $resolver->resolveForStore(self::STORE_ID));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function containerProvider(): array
    {
        return [
            'localized fields' => ['localizedfields'],
            'an object brick' => ['objectbrick'],
            'a field collection' => ['fieldcollection'],
        ];
    }

    /**
     * A class carrying no classification store field at all reaches no index, which is why a store
     * no class uses hands no remap over.
     */
    public function testAClassWithNoClassificationStoreFieldIsNoTarget(): void
    {
        $class = new ClassDefinition();
        $class->setId('CAR');
        $input = new Input();
        $input->setName('name');
        $class->setFieldDefinitions(['name' => $input]);

        $this->assertSame([], $this->createResolver([$class])->resolveForStore(self::STORE_ID));
    }

    /**
     * @param list<ClassDefinition> $classes
     */
    private function createResolver(array $classes): SearchIndexRemapTargetResolver
    {
        return new SearchIndexRemapTargetResolver(
            $this->makeEmpty(ClassDefinitionRepositoryInterface::class, [
                'getClassDefinitions' => $classes,
            ])
        );
    }

    private function createClassCarryingStore(string $classId, int $storeId): ClassDefinition
    {
        $class = new ClassDefinition();
        $class->setId($classId);
        $class->setName($classId);
        $class->setFieldDefinitions([
            self::FIELD_NAME => $this->createStoreField(self::FIELD_NAME, $storeId),
        ]);

        return $class;
    }

    /**
     * The same class, with its one classification store field moved into the named container.
     */
    private function createClassCarryingStoreIn(string $container, string $classId, int $storeId): ClassDefinition
    {
        $class = new ClassDefinition();
        $class->setId($classId);
        $class->setName($classId);
        $class->setFieldDefinitions([$container => $this->createContainerField($container, $storeId)]);

        return $class;
    }

    private function createContainerField(
        string $container,
        int $storeId,
    ): Localizedfields|Objectbricks|Fieldcollections {
        $storeField = $this->createStoreField(self::FIELD_NAME, $storeId);

        if ($container === 'localizedfields') {
            $localized = new Localizedfields();
            $localized->setName($container);
            $localized->setFieldDefinitions([self::FIELD_NAME => $storeField]);

            return $localized;
        }

        if ($container === 'objectbrick') {
            $brick = new ObjectBrickDefinition();
            $brick->setKey(self::BRICK_KEY);
            $brick->addFieldDefinition(self::FIELD_NAME, $storeField);
            RuntimeCache::set('objectbrick_' . self::BRICK_KEY, $brick);

            $bricks = new Objectbricks();
            $bricks->setName($container);
            $bricks->setAllowedTypes([self::BRICK_KEY]);

            return $bricks;
        }

        $collection = new FieldCollectionDefinition();
        $collection->setKey(self::COLLECTION_KEY);
        $collection->addFieldDefinition(self::FIELD_NAME, $storeField);
        RuntimeCache::set('fieldcollection_' . self::COLLECTION_KEY, $collection);

        $collections = new Fieldcollections();
        $collections->setName($container);
        $collections->setAllowedTypes([self::COLLECTION_KEY]);

        return $collections;
    }

    private function createStoreField(string $name, int $storeId): Classificationstore
    {
        $field = new Classificationstore();
        $field->setName($name);
        $field->setStoreId($storeId);

        return $field;
    }
}
