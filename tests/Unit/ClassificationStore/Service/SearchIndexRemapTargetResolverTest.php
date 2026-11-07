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
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;

/**
 * Which search indices a store reaches: one per data object class carrying a field of it, each at
 * most once, and none at all while no class does.
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

    private function createStoreField(string $name, int $storeId): Classificationstore
    {
        $field = new Classificationstore();
        $field->setName($name);
        $field->setStoreId($storeId);

        return $field;
    }
}
