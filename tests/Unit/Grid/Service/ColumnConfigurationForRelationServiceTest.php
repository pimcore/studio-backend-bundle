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

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\DotNotationParserInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationForRelationService;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
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

    private function createColumnConfiguration(string $key): ColumnConfiguration
    {
        return new ColumnConfiguration(
            $key,
            ['data_object'],
            false,
            false,
            false,
            false,
            false,
            null,
            'string',
            'string',
            [],
        );
    }
}
