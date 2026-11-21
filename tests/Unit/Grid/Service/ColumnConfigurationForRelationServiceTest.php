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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\DotNotationParserInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationForRelationService;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Support\Fixture\VisibleFieldsManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ColumnConfigurationForRelationServiceTest extends Unit
{
    public function testGetAvailableDataObjectColumnConfigurationForRelationSkipsFolderPseudoClass(): void
    {
        $fieldDefinition = new ManyToManyObjectRelation();
        $fieldDefinition->setName('relatedCars');
        $fieldDefinition->setClasses([
            ['classes' => 'folder'],
            ['classes' => 'Car'],
        ]);
        $fieldDefinition->setVisibleFields('id');

        $carClassDefinition = new ClassDefinition();
        $carClassDefinition->setId('123');

        $service = $this->createService(
            classDefinitionResolver: $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => new ClassDefinition(),
                'getByName' => static fn (string $name): ?ClassDefinition => $name === 'folder' ? null : $carClassDefinition,
            ]),
            dotNotationParser: $this->makeEmpty(DotNotationParserInterface::class, [
                'parseByClassId' => new FieldDefinitionWrapper($fieldDefinition, 'object', 'relatedCars'),
            ]),
            columnConfigurationService: $this->makeEmpty(ColumnConfigurationServiceInterface::class, [
                'getAvailableDataObjectColumnConfiguration' => [$this->createColumnConfiguration('id')],
            ]),
        );

        $result = $service->getAvailableDataObjectColumnConfigurationForRelation(
            'CAR',
            'relatedCars',
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertSame('id', $result['id']->getKey());
    }

    public function testGetAvailableDataObjectColumnConfigurationForRelationSkipsUnresolvableClass(): void
    {
        $fieldDefinition = new ManyToManyObjectRelation();
        $fieldDefinition->setName('relatedCars');
        $fieldDefinition->setClasses([
            ['classes' => 'DeletedClass'],
            ['classes' => 'Car'],
        ]);
        $fieldDefinition->setVisibleFields('id');

        $carClassDefinition = new ClassDefinition();
        $carClassDefinition->setId('123');

        $service = $this->createService(
            classDefinitionResolver: $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => new ClassDefinition(),
                'getByName' => static fn (string $name): ?ClassDefinition => $name === 'DeletedClass' ? null : $carClassDefinition,
            ]),
            dotNotationParser: $this->makeEmpty(DotNotationParserInterface::class, [
                'parseByClassId' => new FieldDefinitionWrapper($fieldDefinition, 'object', 'relatedCars'),
            ]),
            columnConfigurationService: $this->makeEmpty(ColumnConfigurationServiceInterface::class, [
                'getAvailableDataObjectColumnConfiguration' => [$this->createColumnConfiguration('id')],
            ]),
        );

        $result = $service->getAvailableDataObjectColumnConfigurationForRelation(
            'CAR',
            'relatedCars',
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertSame('id', $result['id']->getKey());
    }

    public function testGetAvailableDataObjectColumnConfigurationForRelationSkipsFolderPseudoClassForAdvancedRelation(): void
    {
        $fieldDefinition = new AdvancedManyToManyObjectRelation();
        $fieldDefinition->setName('relatedCars');
        $fieldDefinition->setAllowedClassId('folder');
        $fieldDefinition->setVisibleFields('id');

        $service = $this->createService(
            classDefinitionResolver: $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => new ClassDefinition(),
            ]),
            dotNotationParser: $this->makeEmpty(DotNotationParserInterface::class, [
                'parseByClassId' => new FieldDefinitionWrapper($fieldDefinition, 'object', 'relatedCars'),
            ]),
            columnConfigurationService: $this->makeEmpty(ColumnConfigurationServiceInterface::class),
        );

        $result = $service->getAvailableDataObjectColumnConfigurationForRelation(
            'CAR',
            'relatedCars',
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertSame([], $result);
    }

    public function testGetAvailableDataObjectColumnConfigurationForRelationSkipsUnresolvableClassForAdvancedRelation(): void
    {
        $fieldDefinition = new AdvancedManyToManyObjectRelation();
        $fieldDefinition->setName('relatedCars');
        $fieldDefinition->setAllowedClassId('DeletedClass');
        $fieldDefinition->setVisibleFields('id');

        $service = $this->createService(
            classDefinitionResolver: $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => new ClassDefinition(),
                'getByName' => static fn (): ?ClassDefinition => null,
            ]),
            dotNotationParser: $this->makeEmpty(DotNotationParserInterface::class, [
                'parseByClassId' => new FieldDefinitionWrapper($fieldDefinition, 'object', 'relatedCars'),
            ]),
            columnConfigurationService: $this->makeEmpty(ColumnConfigurationServiceInterface::class),
        );

        $result = $service->getAvailableDataObjectColumnConfigurationForRelation(
            'CAR',
            'relatedCars',
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertSame([], $result);
    }

    public function testManyToManyRelationWithoutVisibleFieldsHasNoColumns(): void
    {
        $fieldDefinition = new VisibleFieldsManyToManyRelation(['creationDate' => ['object']]);
        $fieldDefinition->setName('related');

        $service = $this->createService(
            classDefinitionResolver: $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => new ClassDefinition(),
            ]),
            dotNotationParser: $this->makeEmpty(DotNotationParserInterface::class, [
                'parseByClassId' => new FieldDefinitionWrapper($fieldDefinition, 'object', 'related'),
            ]),
            columnConfigurationService: $this->makeEmpty(ColumnConfigurationServiceInterface::class, [
                'getAvailableDataObjectColumnConfiguration' => Expected::never(),
                'getAvailableAssetColumnConfiguration' => Expected::never(),
            ]),
        );

        $this->assertSame(
            [],
            $service->getAvailableDataObjectColumnConfigurationForRelation(
                'CAR',
                'related',
                $this->makeEmpty(UserInterface::class)
            )
        );
    }

    public function testManyToManyRelationColumnsComeFromTheFirstSourceTheUserMaySee(): void
    {
        $fieldDefinition = new VisibleFieldsManyToManyRelation([
            'creationDate' => ['object', 'asset'],
            'color' => ['object:Product', 'object:Category'],
            'title' => ['object:Product', 'asset'],
            'copyright' => ['asset'],
            'secret' => ['object:Product'],
        ]);
        $fieldDefinition->setName('related');
        $fieldDefinition->setVisibleFields('copyright,color,removedField,title,secret,creationDate');

        $productClass = new ClassDefinition();
        $productClass->setId('PROD');
        $categoryClass = new ClassDefinition();
        $categoryClass->setId('CAT');

        $productColor = $this->createColumnConfiguration('color', 'dataobject.adapter', 'select');
        $categoryColor = $this->createColumnConfiguration('color', 'dataobject.adapter', 'input');
        $assetTitle = $this->createColumnConfiguration('title', 'metadata.textarea', 'textarea', ['predefined_metadata']);
        $assetCopyright = $this->createColumnConfiguration('copyright', 'metadata.input', 'input', ['default_metadata']);
        $assetCreationDate = $this->createColumnConfiguration('creationDate', 'system.datetime', 'datetime', ['system']);

        $requestedClasses = [];
        $service = $this->createService(
            classDefinitionResolver: $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => new ClassDefinition(),
                'getByName' => static fn (string $name): ?ClassDefinition => match ($name) {
                    'Product' => $productClass,
                    'Category' => $categoryClass,
                    default => null,
                },
            ]),
            dotNotationParser: $this->makeEmpty(DotNotationParserInterface::class, [
                'parseByClassId' => new FieldDefinitionWrapper($fieldDefinition, 'object', 'related'),
            ]),
            columnConfigurationService: $this->makeEmpty(ColumnConfigurationServiceInterface::class, [
                'getAvailableDataObjectColumnConfiguration' => function (?string $classId) use (
                    &$requestedClasses,
                    $productColor,
                    $categoryColor
                ): array {
                    $requestedClasses[] = $classId;

                    return match ($classId) {
                        // "title" and "secret" are fields of the class the user may not see: no columns for them
                        'PROD' => [
                            $this->createColumnConfiguration('id', 'system.id', 'id', ['system']),
                            $productColor,
                        ],
                        'CAT' => [$categoryColor],
                        default => [],
                    };
                },
                'getAvailableAssetColumnConfiguration' => Expected::once([
                    $assetCreationDate,
                    $this->createColumnConfiguration('title', 'metadata.input', 'input', ['default_metadata']),
                    $assetCopyright,
                    $assetTitle,
                ]),
            ]),
        );

        $result = $service->getAvailableDataObjectColumnConfigurationForRelation(
            'CAR',
            'related',
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertSame(['copyright', 'color', 'title', 'creationDate'], array_keys($result));
        $this->assertSame($assetCopyright, $result['copyright']);
        $this->assertSame($productColor, $result['color'], 'the first class offering a field describes it');
        $this->assertSame(
            $assetTitle,
            $result['title'],
            'a class field the user may not see falls back to the next source, predefined metadata first'
        );
        $this->assertSame($assetCreationDate, $result['creationDate']);
        $this->assertSame(['PROD'], $requestedClasses, 'class columns are only loaded where needed, once per class');
    }

    public function testVisibleFieldClassColumnConfigurationKeepsTheClassFieldColumnsByKey(): void
    {
        $productClass = new ClassDefinition();
        $productClass->setId('PROD');

        $color = $this->createColumnConfiguration('color', 'dataobject.adapter', 'select');

        $service = $this->createService(
            classDefinitionResolver: $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getByName' => static fn (string $name): ?ClassDefinition => $name === 'Product' ? $productClass : null,
            ]),
            dotNotationParser: $this->makeEmpty(DotNotationParserInterface::class),
            columnConfigurationService: $this->makeEmpty(ColumnConfigurationServiceInterface::class, [
                'getAvailableDataObjectColumnConfiguration' => [
                    $this->createColumnConfiguration('id', 'system.id', 'id', ['system']),
                    $color,
                    $this->createColumnConfiguration('color', 'dataobject.adapter', 'input'),
                    $this->createColumnConfiguration('brick', 'dataobject.objectbrick', 'input'),
                ],
            ]),
        );

        $user = $this->makeEmpty(UserInterface::class);

        $this->assertSame(['color' => $color], $service->getVisibleFieldClassColumnConfiguration('Product', $user));
        $this->assertSame([], $service->getVisibleFieldClassColumnConfiguration('Deleted', $user));
    }

    private function createService(
        ClassDefinitionResolverInterface $classDefinitionResolver,
        ColumnConfigurationServiceInterface $columnConfigurationService,
        DotNotationParserInterface $dotNotationParser,
    ): ColumnConfigurationForRelationService {
        return new ColumnConfigurationForRelationService(
            $classDefinitionResolver,
            $columnConfigurationService,
            $dotNotationParser,
        );
    }

    private function createColumnConfiguration(
        string $key,
        string $type = 'string',
        string $frontendType = 'string',
        array $group = ['data_object']
    ): ColumnConfiguration {
        return new ColumnConfiguration(
            $key,
            $group,
            false,
            false,
            false,
            false,
            false,
            null,
            $type,
            $frontendType,
            [],
        );
    }
}
