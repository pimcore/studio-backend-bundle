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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\ReverseObjectRelationAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\ReverseRelationOwnerTestObject;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;

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
    private function makeElement(): Concrete
    {
        return $this->makeEmpty(Concrete::class, [
            'getId' => self::ELEMENT_ID,
            'getRelationData' => [['dest_id' => self::OWNER_ID, 'id' => self::OWNER_ID]],
        ]);
    }

    private function makeOwner(Data $ownerField, mixed $currentValue): ReverseRelationOwnerTestObject
    {
        $owner = new ReverseRelationOwnerTestObject();
        $owner->setId(self::OWNER_ID);
        $owner->setClass($this->makeOwnerClass($ownerField));
        $owner->setPublisher($currentValue);

        return $owner;
    }

    /**
     * @param array<int, array{id: int}> $remainingRelations the reverse relation entries after the edit
     *
     * @throws Exception
     */
    private function callAdapter(Concrete $element, Concrete $owner, array $remainingRelations): void
    {
        $adapter = new ReverseObjectRelationAdapter(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, ['getByName' => $owner->getClass()]),
            $this->makeEmpty(ConcreteObjectResolverInterface::class, ['getById' => $owner]),
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
