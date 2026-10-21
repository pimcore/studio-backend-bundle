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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Data\Adapter;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\ReverseObjectRelationAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\ReverseRelationOwnerTestObject;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use function array_map;
use function is_array;

require_once __DIR__ . '/../../../../Support/Fixture/ReverseRelationOwnerTestObject.php';

/**
 * Regression tests for pimcore/platform-version#376: removing an entry from a reverse relation
 * must not silently fail when the owner-side relation field is mandatory.
 *
 * @internal
 */
final class ReverseObjectRelationAdapterTest extends Unit
{
    private const ELEMENT_ID = 10;

    private const OWNER_ID = 20;

    private const OWNER_FIELD = 'publisher';

    /**
     * @throws Exception
     */
    public function testRemovingFromMandatoryManyToOneRaisesValidationError(): void
    {
        $element = $this->makeElement();
        $owner = $this->makeOwner($this->makeManyToOne(mandatory: true), $element);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(self::OWNER_FIELD);

        try {
            $this->callAdapter($element, $owner, remainingRelations: []);
        } finally {
            $this->assertSame($element, $owner->getPublisher(), 'the owner must keep its relation');
            $this->assertSame(0, $owner->getSaveCount(), 'the owner must not be saved');
        }
    }

    /**
     * @throws Exception
     */
    public function testRemovingFromOptionalManyToOneClearsAndSavesOwner(): void
    {
        $element = $this->makeElement();
        $owner = $this->makeOwner($this->makeManyToOne(mandatory: false), $element);

        $this->callAdapter($element, $owner, remainingRelations: []);

        $this->assertNull($owner->getPublisher());
        $this->assertSame(1, $owner->getSaveCount());
    }

    /**
     * @throws Exception
     */
    public function testRemovingLastEntryFromMandatoryManyToManyRaisesValidationError(): void
    {
        $element = $this->makeElement();
        $owner = $this->makeOwner($this->makeManyToMany(mandatory: true), [$element]);

        $this->expectException(ValidationException::class);

        try {
            $this->callAdapter($element, $owner, remainingRelations: []);
        } finally {
            $this->assertSame([$element], $owner->getPublisher());
            $this->assertSame(0, $owner->getSaveCount());
        }
    }

    /**
     * @throws Exception
     */
    public function testRemovingOneOfSeveralEntriesFromMandatoryManyToManyIsAllowed(): void
    {
        $element = $this->makeElement();
        $other = $this->makeEmpty(Concrete::class, ['getId' => 99]);
        $owner = $this->makeOwner($this->makeManyToMany(mandatory: true), [$element, $other]);

        $this->callAdapter($element, $owner, remainingRelations: []);

        $this->assertSame([$other], array_values($owner->getPublisher()));
        $this->assertSame(1, $owner->getSaveCount());
    }

    /**
     * @throws Exception
     */
    public function testOwnerValidationFailureOnSaveIsNotSwallowed(): void
    {
        $element = $this->makeElement();
        $owner = new class() extends ReverseRelationOwnerTestObject {
            public function save(array $parameters = []): static
            {
                throw new ValidationException('Validation failed: some other rule');
            }
        };
        $owner->setId(self::OWNER_ID);
        $owner->setClass($this->makeOwnerClass($this->makeManyToOne(mandatory: false)));
        $owner->setPublisher($element);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('some other rule');

        $this->callAdapter($element, $owner, remainingRelations: []);
    }

    /**
     * @throws Exception
     */
    public function testRejectedRemovalLeavesEarlierOwnersUntouched(): void
    {
        $element = $this->makeElement(ownerIds: [self::OWNER_ID, self::OWNER_ID + 1]);
        $other = $this->makeEmpty(Concrete::class, ['getId' => 99]);
        $allowedOwner = $this->makeOwner($this->makeManyToMany(mandatory: true), [$element, $other]);
        $rejectedOwner = $this->makeOwner($this->makeManyToMany(mandatory: true), [$element], id: self::OWNER_ID + 1);

        $this->expectException(ValidationException::class);

        try {
            $this->callAdapter($element, [$allowedOwner, $rejectedOwner], remainingRelations: []);
        } finally {
            $this->assertSame([$element, $other], $allowedOwner->getPublisher());
            $this->assertSame(0, $allowedOwner->getSaveCount());
            $this->assertSame(0, $rejectedOwner->getSaveCount());
        }
    }

    /**
     * Regression: an owner that holds the element more than once (repeated assignments) must lose
     * every occurrence in one write.
     *
     * @throws Exception
     */
    public function testRepeatedOwnerAssignmentsAreAllRemovedAndSavedOnce(): void
    {
        $element = $this->makeElement(ownerIds: [self::OWNER_ID, self::OWNER_ID]);
        $other = $this->makeEmpty(Concrete::class, ['getId' => 99]);
        $owner = $this->makeOwner($this->makeManyToMany(mandatory: false), [$element, $element, $other]);

        $this->callAdapter($element, $owner, remainingRelations: []);

        $this->assertSame([$other], $owner->getPublisher());
        $this->assertSame(1, $owner->getSaveCount());
    }

    /**
     * @throws Exception
     */
    public function testRemovingRepeatedLastEntriesFromMandatoryManyToManyRaisesValidationError(): void
    {
        $element = $this->makeElement(ownerIds: [self::OWNER_ID, self::OWNER_ID]);
        $owner = $this->makeOwner($this->makeManyToMany(mandatory: true), [$element, $element]);

        $this->expectException(ValidationException::class);

        try {
            $this->callAdapter($element, $owner, remainingRelations: []);
        } finally {
            $this->assertSame([$element, $element], $owner->getPublisher());
            $this->assertSame(0, $owner->getSaveCount());
        }
    }

    /**
     * Core skips the mandatory check for unpublished objects, so the removal must go through.
     *
     * @throws Exception
     */
    public function testUnpublishedOwnerIsNotSubjectToMandatoryCheck(): void
    {
        $element = $this->makeElement();
        $owner = $this->makeOwner($this->makeManyToOne(mandatory: true), $element, published: false);

        $this->callAdapter($element, $owner, remainingRelations: []);

        $this->assertNull($owner->getPublisher());
        $this->assertSame(1, $owner->getSaveCount());
    }

    /**
     * Core accepts an empty inheritable field when the parent provides a value, so must the removal.
     *
     * @throws Exception
     */
    public function testInheritedParentValueSatisfiesMandatoryCheck(): void
    {
        $element = $this->makeElement();
        $owner = $this->makeOwner($this->makeManyToOne(mandatory: true), $element);
        $owner->getClass()->setAllowInherit(true);
        $parent = new ReverseRelationOwnerTestObject();
        $parent->setId(self::OWNER_ID + 5);
        $parent->setPublisher($this->makeEmpty(Concrete::class, ['getId' => 77]));
        $owner->setNextParentForInheritance($parent);

        $this->callAdapter($element, $owner, remainingRelations: []);

        $this->assertNull($owner->getPublisher());
        $this->assertSame(1, $owner->getSaveCount());
    }

    /**
     * @throws Exception
     */
    public function testInheritanceWithoutParentValueStillRejectsMandatoryRemoval(): void
    {
        $element = $this->makeElement();
        $owner = $this->makeOwner($this->makeManyToOne(mandatory: true), $element);
        $owner->getClass()->setAllowInherit(true);
        $parent = new ReverseRelationOwnerTestObject();
        $parent->setId(self::OWNER_ID + 5);
        $owner->setNextParentForInheritance($parent);

        $this->expectException(ValidationException::class);

        $this->callAdapter($element, $owner, remainingRelations: []);
    }

    /**
     * Relations hold mixed element types; an asset or document sharing the numeric id must stay.
     *
     * @throws Exception
     */
    public function testMixedTypeEntriesWithSameIdOnlyLoseTheDataObject(): void
    {
        $element = $this->makeElement();
        $asset = $this->makeEmpty(Asset::class, ['getId' => self::ELEMENT_ID]);
        $other = $this->makeEmpty(Concrete::class, ['getId' => 99]);
        $owner = $this->makeOwner($this->makeManyToMany(mandatory: true), [$element, $asset, $other]);

        $this->callAdapter($element, $owner, remainingRelations: []);

        $this->assertSame([$asset, $other], $owner->getPublisher());
        $this->assertSame(1, $owner->getSaveCount());
    }

    /**
     * @throws Exception
     */
    public function testAssetWithSameIdSatisfiesMandatoryCheckAfterRemoval(): void
    {
        $element = $this->makeElement();
        $asset = $this->makeEmpty(Asset::class, ['getId' => self::ELEMENT_ID]);
        $owner = $this->makeOwner($this->makeManyToMany(mandatory: true), [$element, $asset]);

        $this->callAdapter($element, $owner, remainingRelations: []);

        $this->assertSame([$asset], $owner->getPublisher());
        $this->assertSame(1, $owner->getSaveCount());
    }

    /**
     * Advanced relations wrap their targets in metadata objects; retained entries keep their wrapper.
     *
     * @throws Exception
     */
    public function testMetadataWrappedEntriesKeepTheirMetadata(): void
    {
        $element = $this->makeElement();
        $removed = new ObjectMetadata(self::OWNER_FIELD, ['note'], $element);
        $kept = new ObjectMetadata(self::OWNER_FIELD, ['note'], $this->makeEmpty(Concrete::class, ['getId' => 99]));
        $owner = $this->makeOwner($this->makeManyToMany(mandatory: true), [$removed, $kept]);

        $this->callAdapter($element, $owner, remainingRelations: []);

        $this->assertSame([$kept], $owner->getPublisher());
        $this->assertSame(1, $owner->getSaveCount());
    }

    /**
     * A parent that loses its own value in the same edit cannot satisfy the child's inherited
     * mandatory field; nothing may be written when the child is rejected, even with the parent first.
     *
     * @throws Exception
     */
    public function testPlannedParentRemovalDoesNotSatisfyChildInheritance(): void
    {
        $parentId = self::OWNER_ID + 5;
        $element = $this->makeElement(ownerIds: [$parentId, self::OWNER_ID]);
        $parent = $this->makeOwner($this->makeManyToMany(mandatory: true), [$element], id: $parentId, published: false);
        $parent->getClass()->setAllowInherit(true);
        $child = $this->makeOwner($this->makeManyToMany(mandatory: true), [$element]);
        $child->getClass()->setAllowInherit(true);
        $child->setNextParentForInheritance($parent);

        $this->expectException(ValidationException::class);

        try {
            $this->callAdapter($element, [$parent, $child], remainingRelations: []);
        } finally {
            $this->assertSame([$element], $parent->getPublisher());
            $this->assertSame(0, $parent->getSaveCount());
            $this->assertSame([$element], $child->getPublisher());
            $this->assertSame(0, $child->getSaveCount());
        }
    }

    private function makeManyToOne(bool $mandatory): ManyToOneRelation
    {
        return (new ManyToOneRelation())->setName(self::OWNER_FIELD)->setMandatory($mandatory);
    }

    private function makeManyToMany(bool $mandatory): ManyToManyObjectRelation
    {
        return (new ManyToManyObjectRelation())->setName(self::OWNER_FIELD)->setMandatory($mandatory);
    }

    private function makeOwnerClass(Data $ownerField): ClassDefinition
    {
        return (new ClassDefinition())->setId('owner')->addFieldDefinition(self::OWNER_FIELD, $ownerField);
    }

    /**
     * @throws Exception
     */
    /**
     * @param int[] $ownerIds
     */
    private function makeElement(array $ownerIds = [self::OWNER_ID]): Concrete
    {
        return $this->makeEmpty(Concrete::class, [
            'getId' => self::ELEMENT_ID,
            'getRelationData' => array_map(static fn (int $id): array => ['dest_id' => $id, 'id' => $id], $ownerIds),
        ]);
    }

    private function makeOwner(
        Data $ownerField,
        mixed $currentValue,
        int $id = self::OWNER_ID,
        bool $published = true
    ): ReverseRelationOwnerTestObject {
        $owner = new ReverseRelationOwnerTestObject();
        $owner->setId($id);
        $owner->setPublished($published);
        $owner->setClass($this->makeOwnerClass($ownerField));
        $owner->setPublisher($currentValue);

        return $owner;
    }

    /**
     * @param array<int, array{id: int}> $remainingRelations the reverse relation entries after the edit
     *
     * @throws Exception
     */
    private function callAdapter(Concrete $element, Concrete|array $owners, array $remainingRelations): void
    {
        $owners = is_array($owners) ? $owners : [$owners];
        $ownersById = [];
        foreach ($owners as $owner) {
            $ownersById[$owner->getId()] = $owner;
        }

        $adapter = new ReverseObjectRelationAdapter(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, ['getByName' => $owners[0]->getClass()]),
            $this->makeEmpty(ConcreteObjectResolverInterface::class, [
                'getById' => static fn (int $id): ?Concrete => $ownersById[$id] ?? null,
            ]),
            $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'useInheritedValues' => static fn (bool $inherit, callable $fn, array $args = []): mixed
                    => $fn(...$args),
            ]),
            $this->makeEmpty(LoggerInterface::class),
            $this->makeEmpty(RelationDataServiceInterface::class, ['getSetterData' => $remainingRelations]),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['getId' => 1]),
            ])
        );

        $fieldDefinition = (new ReverseObjectRelation())
            ->setName('reverse')
            ->setOwnerClassName('Owner')
            ->setOwnerFieldName(self::OWNER_FIELD);

        $adapter->getDataForSetter(
            $element,
            $fieldDefinition,
            'reverse',
            ['reverse' => $remainingRelations],
            $this->makeEmpty(UserInterface::class)
        );
    }
}
