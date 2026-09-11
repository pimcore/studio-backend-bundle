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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceService;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\UrlSlug;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class InheritanceServiceTest extends Unit
{
    private const int OBJECT_ID = 318;

    private const int PARENT_ID = 42;

    private const int GRANDPARENT_ID = 7;

    private const string FIELD_KEY = 'name';

    public function testNonInheritableFieldTypeIsFlaggedAsNotInheritable(): void
    {
        $parent = $this->createObject(self::PARENT_ID, 'parent-slug');
        $object = $this->createObject(self::OBJECT_ID, 'own-slug', $parent);

        $result = $this->createService()->processFieldDefinition($object, new UrlSlug(), 'urlSlug', $this->optIn());

        $this->assertInstanceOf(InheritanceData::class, $result);
        $this->assertSame(self::OBJECT_ID, $result->getObjectId());
        $this->assertFalse($result->isInherited());
        $this->assertFalse($result->isInheritable());
        $this->assertNull($result->getInheritedValue());
    }

    public function testFieldTypeWithoutAdapterIsFlaggedAsNotInheritable(): void
    {
        $parent = $this->createObject(self::PARENT_ID, 'parent value');
        $object = $this->createObject(self::OBJECT_ID, 'own value', $parent);

        $result = $this->createService(adapter: null)->processFieldDefinition($object, new Input(), self::FIELD_KEY, $this->optIn());

        $this->assertInstanceOf(InheritanceData::class, $result);
        $this->assertFalse($result->isInheritable());
        $this->assertNull($result->getInheritedValue());
    }

    public function testOverriddenValueReportsTheInheritedValueOfTheNextAncestor(): void
    {
        $parent = $this->createObject(self::PARENT_ID, 'parent value');
        $object = $this->createObject(self::OBJECT_ID, 'own value', $parent);

        $result = $this->createService()->processFieldDefinition($object, new Input(), self::FIELD_KEY, $this->optIn());

        $this->assertInstanceOf(InheritanceData::class, $result);
        $this->assertSame(self::OBJECT_ID, $result->getObjectId());
        $this->assertFalse($result->isInherited());
        $this->assertTrue($result->isInheritable());
        $this->assertSame('parent value', $result->getInheritedValue());
    }

    public function testInheritedValueReportsOriginAndInheritedValue(): void
    {
        $parent = $this->createObject(self::PARENT_ID, 'parent value');
        $object = $this->createObject(self::OBJECT_ID, '', $parent);

        $result = $this->createService()->processFieldDefinition($object, new Input(), self::FIELD_KEY, $this->optIn());

        $this->assertInstanceOf(InheritanceData::class, $result);
        $this->assertSame(self::PARENT_ID, $result->getObjectId());
        $this->assertTrue($result->isInherited());
        $this->assertTrue($result->isInheritable());
        $this->assertSame('parent value', $result->getInheritedValue());
    }

    public function testInheritedValueSkipsAncestorsWithoutValue(): void
    {
        $grandparent = $this->createObject(self::GRANDPARENT_ID, 'grandparent value');
        $parent = $this->createObject(self::PARENT_ID, '', $grandparent);
        $object = $this->createObject(self::OBJECT_ID, 'own value', $parent);

        $result = $this->createService()->processFieldDefinition($object, new Input(), self::FIELD_KEY, $this->optIn());

        $this->assertInstanceOf(InheritanceData::class, $result);
        $this->assertFalse($result->isInherited());
        $this->assertSame('grandparent value', $result->getInheritedValue());
    }

    public function testInheritedValueIsNullWhenNoAncestorHasAValue(): void
    {
        $parent = $this->createObject(self::PARENT_ID, '');
        $object = $this->createObject(self::OBJECT_ID, 'own value', $parent);

        $result = $this->createService()->processFieldDefinition($object, new Input(), self::FIELD_KEY, $this->optIn());

        $this->assertInstanceOf(InheritanceData::class, $result);
        $this->assertSame(self::OBJECT_ID, $result->getObjectId());
        $this->assertFalse($result->isInherited());
        $this->assertTrue($result->isInheritable());
        $this->assertNull($result->getInheritedValue());
    }

    public function testInheritedValueIsNullWithoutParentForInheritance(): void
    {
        $object = $this->createObject(self::OBJECT_ID, 'own value');

        $result = $this->createService()->processFieldDefinition($object, new Input(), self::FIELD_KEY, $this->optIn());

        $this->assertInstanceOf(InheritanceData::class, $result);
        $this->assertTrue($result->isInheritable());
        $this->assertNull($result->getInheritedValue());
    }

    public function testInheritedValueIsNormalizedThroughTheDataAdapter(): void
    {
        $parent = $this->createObject(self::PARENT_ID, 'parent value');
        $object = $this->createObject(self::OBJECT_ID, 'own value', $parent);

        $result = $this->createService(adapter: $this->createNormalizingAdapter())
            ->processFieldDefinition($object, new Input(), self::FIELD_KEY, $this->optIn());

        $this->assertInstanceOf(InheritanceData::class, $result);
        $this->assertSame('PARENT VALUE', $result->getInheritedValue());
    }

    public function testGetOriginIdIsUnchanged(): void
    {
        $grandparent = $this->createObject(self::GRANDPARENT_ID, 'grandparent value');
        $parent = $this->createObject(self::PARENT_ID, '', $grandparent);
        $inheriting = $this->createObject(self::OBJECT_ID, '', $parent);
        $overriding = $this->createObject(self::OBJECT_ID, 'own value', $parent);
        $orphan = $this->createObject(self::OBJECT_ID, '');

        $service = $this->createService();

        $this->assertSame(self::GRANDPARENT_ID, $service->getOriginId($inheriting, new Input(), self::FIELD_KEY));
        $this->assertSame(self::OBJECT_ID, $service->getOriginId($overriding, new Input(), self::FIELD_KEY));
        $this->assertSame(self::OBJECT_ID, $service->getOriginId($orphan, new Input(), self::FIELD_KEY));
    }

    public function testGetInheritanceDataIsEmptyWithoutConcreteParent(): void
    {
        $object = $this->createObject(self::OBJECT_ID, 'own value');

        $result = $this->createService()->getInheritanceData($object, [self::FIELD_KEY => new Input()]);

        $this->assertSame([], $result);
    }

    public function testGetInheritanceDataBuildsMetaDataPerField(): void
    {
        $parent = $this->createObject(self::PARENT_ID, 'parent value');
        $object = $this->createObject(self::OBJECT_ID, 'own value', $parent);

        $result = $this->createService()->getInheritanceData(
            $object,
            [self::FIELD_KEY => new Input(), 'urlSlug' => new UrlSlug()],
            true
        );

        $this->assertArrayHasKey('metaData', $result);
        $this->assertInstanceOf(InheritanceData::class, $result['metaData'][self::FIELD_KEY]);
        $this->assertTrue($result['metaData'][self::FIELD_KEY]->isInheritable());
        $this->assertSame('parent value', $result['metaData'][self::FIELD_KEY]->getInheritedValue());
        $this->assertInstanceOf(InheritanceData::class, $result['metaData']['urlSlug']);
        $this->assertFalse($result['metaData']['urlSlug']->isInheritable());
    }

    public function testGetInheritanceDataDoesNotResolveInheritedValuesByDefault(): void
    {
        $parent = $this->createObject(self::PARENT_ID, 'parent value');
        $object = $this->createObject(self::OBJECT_ID, 'own value', $parent);

        $result = $this->createService()->getInheritanceData($object, [self::FIELD_KEY => new Input()]);

        $this->assertInstanceOf(InheritanceData::class, $result['metaData'][self::FIELD_KEY]);
        $this->assertTrue($result['metaData'][self::FIELD_KEY]->isInheritable());
        $this->assertNull($result['metaData'][self::FIELD_KEY]->getInheritedValue());
    }

    public function testInheritedValueIsNotResolvedWithoutOptIn(): void
    {
        $parent = $this->createObject(self::PARENT_ID, 'parent value');
        $object = $this->makeEmpty(Concrete::class, [
            'getId' => self::OBJECT_ID,
            'get' => 'own value',
            'getParent' => $parent,
            // an own value must not trigger the walk up the tree unless the inherited value was requested
            'getNextParentForInheritance' => Expected::never(),
        ]);
        $service = $this->createService();

        $withoutContext = $service->processFieldDefinition($object, new Input(), self::FIELD_KEY);
        $withPlainContext = $service->processFieldDefinition(
            $object,
            new Input(),
            self::FIELD_KEY,
            new FieldContextData(language: 'en')
        );

        foreach ([$withoutContext, $withPlainContext] as $result) {
            $this->assertInstanceOf(InheritanceData::class, $result);
            $this->assertSame(self::OBJECT_ID, $result->getObjectId());
            $this->assertFalse($result->isInherited());
            $this->assertTrue($result->isInheritable());
            $this->assertNull($result->getInheritedValue());
        }
    }

    public function testInheritedValueOfAnInheritedFieldIsAlsoOptIn(): void
    {
        $parent = $this->createObject(self::PARENT_ID, 'parent value');
        $object = $this->createObject(self::OBJECT_ID, '', $parent);

        $result = $this->createService()->processFieldDefinition($object, new Input(), self::FIELD_KEY);

        $this->assertInstanceOf(InheritanceData::class, $result);
        $this->assertSame(self::PARENT_ID, $result->getObjectId());
        $this->assertTrue($result->isInherited());
        $this->assertNull($result->getInheritedValue());
    }

    public function testOptInSurvivesTheContextCopyForAnAncestor(): void
    {
        $context = new FieldContextData(language: 'en', resolveInheritedValue: true);

        $copy = $context->getContextObjectFromElement($this->createObject(self::PARENT_ID, null));

        $this->assertTrue($copy->shouldResolveInheritedValue());
        $this->assertFalse((new FieldContextData(language: 'en'))->shouldResolveInheritedValue());
    }

    private function optIn(): FieldContextData
    {
        return new FieldContextData(resolveInheritedValue: true);
    }

    private function createObject(int $id, mixed $value, ?Concrete $parent = null): Concrete
    {
        return $this->makeEmpty(Concrete::class, [
            'getId' => $id,
            'get' => $value,
            'getParent' => $parent,
            'getNextParentForInheritance' => $parent,
        ]);
    }

    /**
     * @param SetterDataInterface|null|false $adapter false = plain adapter, null = no adapter for the field type
     */
    private function createService(SetterDataInterface|null|false $adapter = false): InheritanceService
    {
        if ($adapter === false) {
            $adapter = $this->makeEmpty(SetterDataInterface::class);
        }

        return new InheritanceService(
            $this->makeEmpty(DataAdapterServiceInterface::class, [
                'tryDataAdapter' => $adapter,
            ]),
            $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'useInheritedValues' => static fn (bool $inheritValues, callable $fn, array $fnArgs = []) => $fn(...$fnArgs),
            ])
        );
    }

    private function createNormalizingAdapter(): SetterDataInterface
    {
        return new class() implements SetterDataInterface, DataNormalizerInterface {
            public function getDataForSetter(
                Concrete $element,
                Data $fieldDefinition,
                string $key,
                array $data,
                UserInterface $user,
                ?FieldContextData $contextData = null,
                bool $isPatch = false
            ): mixed {
                return null;
            }

            public function normalize(mixed $value, Data $fieldDefinition): mixed
            {
                return strtoupper((string) $value);
            }
        };
    }
}
