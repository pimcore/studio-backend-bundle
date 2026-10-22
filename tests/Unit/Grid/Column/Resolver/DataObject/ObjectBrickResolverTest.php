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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Column\Resolver\DataObject;

use BadMethodCallException;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\ObjectBrickResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\ObjectBrick\Service\ObjectBrickServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Model\UserInterface;
use ReflectionClass;

/**
 * @internal
 */
final class ObjectBrickResolverTest extends Unit
{
    /**
     * A brick that is allowed on the field but not filled in on the object is returned as null by
     * the container getter. Exporting a localized attribute of it must yield an empty cell instead
     * of a "call to a member function get() on null" error that kills the export job.
     */
    public function testResolveForExportReturnsNullForEmptyBrickWithLocale(): void
    {
        $column = new Column(
            key: 'bricks.MyBrick.name',
            locale: 'en',
            type: 'dataobject.objectbrick',
            group: ['objectbrick'],
            config: [],
        );

        $result = $this->createResolver(localizedFields: $this->createLocalizedFields())->resolveForExport(
            $column,
            $this->createElementWithEmptyBrick(),
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertNull($result->getValue());
        $this->assertSame('input', $result->getFieldType());
        $this->assertSame('en', $result->getLocale());
    }

    public function testResolveForExportReturnsNullForEmptyBrickWithoutLocale(): void
    {
        $column = new Column(
            key: 'bricks.MyBrick.name',
            locale: null,
            type: 'dataobject.objectbrick',
            group: ['objectbrick'],
            config: [],
        );

        $result = $this->createResolver()->resolveForExport(
            $column,
            $this->createElementWithEmptyBrick(),
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertNull($result->getValue());
        $this->assertSame('input', $result->getFieldType());
    }

    /**
     * Callers such as the webhooks bundle pass a default locale for every column. A non-localized brick
     * attribute has no localizedfields container, so the locale must be ignored instead of the cell
     * silently becoming null.
     */
    public function testResolveForExportIgnoresLocaleForNonLocalizedAttribute(): void
    {
        $column = new Column(
            key: 'bricks.MyBrick.name',
            locale: 'en',
            type: 'dataobject.objectbrick',
            group: ['objectbrick'],
            config: [],
        );
        $brick = $this->makeEmpty(AbstractData::class, [
            'get' => static fn (string $name) => throw new BadMethodCallException($name),
        ]);

        $result = $this->createResolver(
            localizedFields: null,
            dataService: $this->makeEmpty(DataServiceInterface::class, [
                'getExportFieldValue' => Expected::once(
                    function ($element, $fieldDefinition, $field, FieldContextData $context) use ($brick) {
                        $this->assertSame($brick, $context->getContextObject());
                        $this->assertNull($context->getLegacyParameters()['language']);

                        return 'reworked';
                    }
                ),
            ])
        )->resolveForExport($column, $this->createElement($brick), $this->makeEmpty(UserInterface::class));

        $this->assertSame('reworked', $result->getValue());
    }

    /**
     * A brick with localizedfields still holds plain attributes next to them; only attributes defined
     * in localizedfields take the locale.
     */
    public function testResolveForExportIgnoresLocaleForPlainAttributeOfBrickWithLocalizedFields(): void
    {
        $column = new Column(
            key: 'bricks.MyBrick.plain',
            locale: 'en',
            type: 'dataobject.objectbrick',
            group: ['objectbrick'],
            config: [],
        );
        $brick = $this->makeEmpty(AbstractData::class, [
            'get' => static fn (string $name) => throw new BadMethodCallException($name),
        ]);

        $result = $this->createResolver(
            localizedFields: $this->createLocalizedFields(),
            dataService: $this->makeEmpty(DataServiceInterface::class, [
                'getExportFieldValue' => Expected::once(
                    function ($element, $fieldDefinition, $field, FieldContextData $context) use ($brick) {
                        $this->assertSame($brick, $context->getContextObject());
                        $this->assertNull($context->getLegacyParameters()['language']);

                        return 'plain value';
                    }
                ),
            ])
        )->resolveForExport($column, $this->createElement($brick), $this->makeEmpty(UserInterface::class));

        $this->assertSame('plain value', $result->getValue());
    }

    /**
     * An export or webhook column may still list an attribute that was removed from the brick. That must
     * give an empty cell, not an error that kills the whole export.
     */
    public function testResolveForExportReturnsNullForMissingAttributeDefinition(): void
    {
        $column = new Column(
            key: 'bricks.MyBrick.removed',
            locale: 'en',
            type: 'dataobject.objectbrick',
            group: ['objectbrick'],
            config: [],
        );

        $result = $this->createResolver(
            missingAttribute: 'removed',
        )->resolveForExport(
            $column,
            $this->createElementWithEmptyBrick(),
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertNull($result->getValue());
        $this->assertSame('dataobject.objectbrick', $result->getFieldType());
    }

    /**
     * The whole brick may be gone from the system while a column still lists it: empty cell as well.
     */
    public function testResolveForExportReturnsNullForMissingBrickDefinition(): void
    {
        $column = new Column(
            key: 'bricks.RemovedBrick.name',
            locale: 'en',
            type: 'dataobject.objectbrick',
            group: ['objectbrick'],
            config: [],
        );

        $result = $this->createResolver(
            missingBrick: true,
        )->resolveForExport(
            $column,
            $this->createElementWithEmptyBrick(),
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertNull($result->getValue());
        $this->assertSame('dataobject.objectbrick', $result->getFieldType());
    }

    public function testResolveForExportUsesLocaleForLocalizedAttribute(): void
    {
        $column = new Column(
            key: 'bricks.MyBrick.name',
            locale: 'en',
            type: 'dataobject.objectbrick',
            group: ['objectbrick'],
            config: [],
        );
        $localizedContainer = (new ReflectionClass(Localizedfield::class))->newInstanceWithoutConstructor();
        $brick = $this->makeEmpty(AbstractData::class, ['get' => $localizedContainer]);

        $result = $this->createResolver(
            localizedFields: $this->createLocalizedFields(),
            dataService: $this->makeEmpty(DataServiceInterface::class, [
                'getExportFieldValue' => Expected::once(
                    function ($element, $fieldDefinition, $field, FieldContextData $context) use ($localizedContainer) {
                        $this->assertSame($localizedContainer, $context->getContextObject());
                        $this->assertSame('en', $context->getLegacyParameters()['language']);

                        return 'localized value';
                    }
                ),
            ])
        )->resolveForExport($column, $this->createElement($brick), $this->makeEmpty(UserInterface::class));

        $this->assertSame('localized value', $result->getValue());
    }

    /**
     * Every brick column of a grid row reads the same normalized container and the same inheritance data,
     * and both are expensive to build for a container with many bricks, attributes and locales. They must
     * be built once per element, not once per column, and never shared between elements
     * (pimcore/platform-version#650).
     */
    public function testResolveForCoreElementNormalizesBrickContainerOncePerElement(): void
    {
        $firstContainer = $this->makeEmpty(Objectbrick::class);
        $secondContainer = $this->makeEmpty(Objectbrick::class);
        $first = $this->createCoreElement($firstContainer);
        $second = $this->createCoreElement($secondContainer);

        $resolver = $this->createCoreResolver(
            dataService: $this->makeEmpty(DataServiceInterface::class, [
                'getNormalizedValue' => Expected::exactly(2, static fn ($container) => $container === $firstContainer
                    ? ['MyBrick' => ['name' => 'first name', 'plain' => 'first plain']]
                    : ['MyBrick' => ['name' => 'second name']]),
            ]),
            inheritanceService: $this->makeEmpty(InheritanceServiceInterface::class, [
                'processFieldDefinition' => Expected::exactly(2, []),
            ]),
        );

        $firstName = $resolver->resolveForCoreElement($this->createCoreColumn('bricks.MyBrick.name'), $first);
        $firstPlain = $resolver->resolveForCoreElement($this->createCoreColumn('bricks.MyBrick.plain'), $first);
        $secondName = $resolver->resolveForCoreElement($this->createCoreColumn('bricks.MyBrick.name'), $second);

        $this->assertSame('first name', $firstName->getValue());
        $this->assertSame('first plain', $firstPlain->getValue());
        $this->assertSame('second name', $secondName->getValue());
        $this->assertNull($firstPlain->getInheritance());
    }

    /**
     * A brick or attribute that is not filled in on the object is absent from the normalized container:
     * empty cell, without going through an "undefined array key" error.
     */
    public function testResolveForCoreElementReturnsNullForAttributeMissingInContainer(): void
    {
        $resolver = $this->createCoreResolver(
            dataService: $this->makeEmpty(DataServiceInterface::class, [
                'getNormalizedValue' => Expected::once(['OtherBrick' => ['name' => 'other']]),
            ]),
            inheritanceService: $this->makeEmpty(InheritanceServiceInterface::class, [
                'processFieldDefinition' => Expected::once([]),
            ]),
        );

        $result = $resolver->resolveForCoreElement(
            $this->createCoreColumn('bricks.MyBrick.name'),
            $this->createCoreElement($this->makeEmpty(Objectbrick::class))
        );

        $this->assertNull($result->getValue());
        $this->assertNull($result->getInheritance());
        $this->assertSame('input', $result->getFieldType());
    }

    private function createCoreColumn(string $key): Column
    {
        return new Column(
            key: $key,
            locale: null,
            type: 'dataobject.objectbrick',
            group: ['objectbrick'],
            config: [],
        );
    }

    /**
     * The brick container is read through the dynamic field getter (getBricks()), which Concrete routes
     * through __call.
     */
    private function createCoreElement(Objectbrick $container): Concrete
    {
        return $this->makeEmpty(Concrete::class, [
            'getClassId' => 'product',
            '__call' => static fn (string $method) => $method === 'getBricks' ? $container : null,
        ]);
    }

    private function createCoreResolver(
        DataServiceInterface $dataService,
        InheritanceServiceInterface $inheritanceService,
    ): ObjectBrickResolver {
        $bricksField = new Objectbricks();
        $bricksField->setName('bricks');
        $classDefinition = new ClassDefinition();
        $classDefinition->setAllowInherit(true);
        $classDefinition->addFieldDefinition('bricks', $bricksField);

        return new ObjectBrickResolver(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, ['getById' => $classDefinition]),
            $dataService,
            $inheritanceService,
            $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'useInheritedValues' => static fn (bool $inherited, callable $callback) => $callback(),
            ]),
            $this->makeEmpty(ObjectBrickServiceInterface::class, ['findObjectBrickField' => new Input()]),
            $this->makeEmpty(DefinitionResolverInterface::class),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
        );
    }

    private function createElement(AbstractData $brick): Concrete
    {
        $brickContainer = $this->makeEmpty(Objectbrick::class, ['get' => $brick]);

        return $this->makeEmpty(Concrete::class, ['get' => $brickContainer]);
    }

    private function createLocalizedFields(): Localizedfields
    {
        $input = new Input();
        $input->setName('name');
        $localizedFields = new Localizedfields();
        $localizedFields->setChildren([$input]);

        return $localizedFields;
    }

    private function createElementWithEmptyBrick(): Concrete
    {
        $brickContainer = $this->makeEmpty(Objectbrick::class, ['get' => null]);

        return $this->makeEmpty(Concrete::class, ['get' => $brickContainer]);
    }

    private function createResolver(
        ?Localizedfields $localizedFields = null,
        ?DataServiceInterface $dataService = null,
        ?string $missingAttribute = null,
        bool $missingBrick = false,
    ): ObjectBrickResolver {

        return new ObjectBrickResolver(
            $this->makeEmpty(ClassDefinitionResolverInterface::class),
            $dataService ?? $this->makeEmpty(DataServiceInterface::class, [
                'getExportFieldValue' => Expected::never(),
            ]),
            $this->makeEmpty(InheritanceServiceInterface::class),
            $this->makeEmpty(DataObjectServiceResolverInterface::class),
            $this->makeEmpty(ObjectBrickServiceInterface::class),
            $this->makeEmpty(DefinitionResolverInterface::class, [
                'getByKey' => $missingBrick ? null : $this->makeEmpty(Definition::class, [
                    'getFieldDefinition' => static function (string $key) use ($localizedFields, $missingAttribute) {
                        if ($key === 'localizedfields') {
                            return $localizedFields;
                        }

                        if ($key === $missingAttribute) {
                            return null;
                        }

                        $fieldDefinition = new Input();
                        $fieldDefinition->setName($key);

                        return $fieldDefinition;
                    },
                ]),
            ]),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
        );
    }
}
