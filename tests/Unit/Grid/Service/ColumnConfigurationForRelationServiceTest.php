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
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ColumnConfigurationForRelationServiceTest extends Unit
{
    public function testManyToManyObjectRelationSkipsClassesThatNoLongerExist(): void
    {
        // A relation still listing a class name that was since renamed away,
        // e.g. leftover from https://github.com/pimcore/platform-version/issues/171
        $relation = new ManyToManyObjectRelation();
        $relation->setName('manufacturer');
        $relation->setClasses([
            ['classes' => 'Manufacturer'],
            ['classes' => 'MissingClass'],
        ]);
        $relation->setVisibleFields('title');

        $existingClassDefinition = new ClassDefinition();
        $existingClassDefinition->setId('MANUFACTURER');

        $classResolver = $this->makeEmpty(ClassDefinitionResolverInterface::class, [
            'getById' => new ClassDefinition(),
            'getByName' => static fn (string $name) => $name === 'Manufacturer' ? $existingClassDefinition : null,
        ]);

        $columnConfiguration = new ColumnConfiguration(
            key: 'title',
            group: [],
            sortable: false,
            editable: false,
            exportable: false,
            filterable: false,
            localizable: false,
            locale: null,
            type: 'input',
            frontendType: 'text',
            config: []
        );

        $columnConfigurationService = $this->makeEmpty(ColumnConfigurationServiceInterface::class, [
            'getAvailableDataObjectColumnConfiguration' => Expected::once([$columnConfiguration]),
        ]);

        $dotNotationParser = $this->makeEmpty(DotNotationParserInterface::class, [
            'parseByClassId' => new FieldDefinitionWrapper($relation, 'object', 'manufacturer'),
        ]);

        $service = new ColumnConfigurationForRelationService(
            $classResolver,
            $columnConfigurationService,
            $dotNotationParser
        );

        $result = $service->getAvailableDataObjectColumnConfigurationForRelation(
            'CAR',
            'manufacturer',
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertArrayHasKey('title', $result);
        $this->assertSame($columnConfiguration, $result['title']);
    }

    public function testAdvancedManyToManyObjectRelationReturnsEmptyWhenClassNoLongerExists(): void
    {
        $relation = new AdvancedManyToManyObjectRelation();
        $relation->setName('manufacturer');
        $relation->setAllowedClassId('MissingClass');
        $relation->setVisibleFields('title');

        $classResolver = $this->makeEmpty(ClassDefinitionResolverInterface::class, [
            'getById' => new ClassDefinition(),
            'getByName' => null,
        ]);

        $columnConfigurationService = $this->makeEmpty(ColumnConfigurationServiceInterface::class, [
            'getAvailableDataObjectColumnConfiguration' => Expected::never(),
        ]);

        $dotNotationParser = $this->makeEmpty(DotNotationParserInterface::class, [
            'parseByClassId' => new FieldDefinitionWrapper($relation, 'object', 'manufacturer'),
        ]);

        $service = new ColumnConfigurationForRelationService(
            $classResolver,
            $columnConfigurationService,
            $dotNotationParser
        );

        $result = $service->getAvailableDataObjectColumnConfigurationForRelation(
            'CAR',
            'manufacturer',
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertSame([], $result);
    }
}
