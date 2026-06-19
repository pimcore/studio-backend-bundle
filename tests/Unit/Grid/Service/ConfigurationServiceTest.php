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
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Hydrator\ConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Hydrator\DetailedConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ConfigurationService;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\FavoriteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Configuration\Share\Service\ConfigurationShareServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class ConfigurationServiceTest extends Unit
{
    public function testBuildDefaultConfigurationMatchesPredefinedColumn(): void
    {
        $service = $this->createService();

        $available = [
            $this->createColumnConfiguration('id', ['system']),
        ];
        $predefined = [
            ['key' => 'id', 'group' => 'system'],
        ];

        $result = $service->buildDefaultConfiguration($available, $predefined);

        $columns = $result->getColumns();
        $this->assertCount(1, $columns);
        $this->assertSame('id', $columns[0]->getKey());
        $this->assertSame(['system'], $columns[0]->getGroup());
    }

    public function testBuildDefaultConfigurationExcludesUnmatchedPredefinedColumn(): void
    {
        $service = $this->createService();

        $available = [
            $this->createColumnConfiguration('id', ['system']),
        ];
        $predefined = [
            ['key' => 'nonexistent', 'group' => 'system'],
        ];

        $result = $service->buildDefaultConfiguration($available, $predefined);

        $this->assertCount(0, $result->getColumns());
    }

    public function testBuildDefaultConfigurationExcludesDuplicateMatches(): void
    {
        $service = $this->createService();

        $available = [
            $this->createColumnConfiguration('id', ['system'], 'en'),
            $this->createColumnConfiguration('id', ['system'], 'de'),
        ];
        $predefined = [
            ['key' => 'id', 'group' => 'system'],
        ];

        $result = $service->buildDefaultConfiguration($available, $predefined);

        $this->assertCount(0, $result->getColumns());
    }

    public function testBuildDefaultConfigurationMatchesMultiplePredefinedColumns(): void
    {
        $service = $this->createService();

        $available = [
            $this->createColumnConfiguration('id', ['system']),
            $this->createColumnConfiguration('fullpath', ['system']),
            $this->createColumnConfiguration('creationDate', ['system']),
        ];
        $predefined = [
            ['key' => 'id', 'group' => 'system'],
            ['key' => 'fullpath', 'group' => 'system'],
        ];

        $result = $service->buildDefaultConfiguration($available, $predefined);

        $columns = $result->getColumns();
        $this->assertCount(2, $columns);
        $this->assertSame('id', $columns[0]->getKey());
        $this->assertSame('fullpath', $columns[1]->getKey());
    }

    public function testBuildDefaultConfigurationExcludesHiddenGridColumn(): void
    {
        $classDefinition = new ClassDefinition();
        $classDefinition->setPropertyVisibility([
            'grid' => ['id' => false, 'path' => true],
            'search' => ['id' => true, 'path' => true],
        ]);

        $service = $this->createService(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => $classDefinition,
            ])
        );

        $available = [
            $this->createColumnConfiguration('id', ['system']),
        ];
        $predefined = [
            ['key' => 'id', 'group' => 'system'],
        ];

        $result = $service->buildDefaultConfiguration(
            $available,
            $predefined,
            false,
            true,
            'CAR'
        );

        $this->assertCount(0, $result->getColumns());
    }

    public function testBuildDefaultConfigurationExcludesHiddenSearchColumn(): void
    {
        $classDefinition = new ClassDefinition();
        $classDefinition->setPropertyVisibility([
            'grid' => ['id' => true, 'path' => true],
            'search' => ['id' => false, 'path' => true],
        ]);

        $service = $this->createService(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => $classDefinition,
            ])
        );

        $available = [
            $this->createColumnConfiguration('id', ['system']),
        ];
        $predefined = [
            ['key' => 'id', 'group' => 'system'],
        ];

        $result = $service->buildDefaultConfiguration(
            $available,
            $predefined,
            true,
            false,
            'CAR'
        );

        $this->assertCount(0, $result->getColumns());
    }

    public function testBuildDefaultConfigurationFullpathUsesPathVisibility(): void
    {
        $classDefinition = new ClassDefinition();
        $classDefinition->setPropertyVisibility([
            'grid' => ['fullpath' => true, 'path' => false],
            'search' => [],
        ]);

        $service = $this->createService(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => $classDefinition,
            ])
        );

        $available = [
            $this->createColumnConfiguration('fullpath', ['system']),
        ];
        $predefined = [
            ['key' => 'fullpath', 'group' => 'system'],
        ];

        $result = $service->buildDefaultConfiguration(
            $available,
            $predefined,
            false,
            true,
            'CAR'
        );

        $this->assertCount(0, $result->getColumns());
    }

    public function testBuildDefaultConfigurationNoVisibilityFilteringWithoutClassId(): void
    {
        $service = $this->createService();

        $available = [
            $this->createColumnConfiguration('id', ['system']),
        ];
        $predefined = [
            ['key' => 'id', 'group' => 'system'],
        ];

        $result = $service->buildDefaultConfiguration($available, $predefined);

        $this->assertCount(1, $result->getColumns());
    }

    public function testBuildDefaultConfigurationResolverExceptionSkipsVisibilityFiltering(): void
    {
        $service = $this->createService(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => static function (): never {
                    throw new Exception('Class not found');
                },
            ])
        );

        $available = [
            $this->createColumnConfiguration('id', ['system']),
        ];
        $predefined = [
            ['key' => 'id', 'group' => 'system'],
        ];

        $result = $service->buildDefaultConfiguration(
            $available,
            $predefined,
            false,
            false,
            'NONEXISTENT'
        );

        $this->assertCount(1, $result->getColumns());
    }

    public function testBuildDefaultConfigurationAppendsVisibleSearchColumns(): void
    {
        $fieldDefinition = $this->makeEmpty(Data::class, [
            'getVisibleSearch' => true,
        ]);

        $service = $this->createService();

        $available = [
            $this->createColumnConfiguration(
                'name',
                ['data_object'],
                null,
                ['fieldDefinition' => $fieldDefinition]
            ),
        ];

        $result = $service->buildDefaultConfiguration($available, [], true);

        $columns = $result->getColumns();
        $this->assertCount(1, $columns);
        $this->assertSame('name', $columns[0]->getKey());
    }

    public function testBuildDefaultConfigurationAppendsVisibleGridViewColumns(): void
    {
        $fieldDefinition = $this->makeEmpty(Data::class, [
            'getVisibleGridView' => true,
        ]);

        $service = $this->createService();

        $available = [
            $this->createColumnConfiguration(
                'name',
                ['data_object'],
                null,
                ['fieldDefinition' => $fieldDefinition]
            ),
        ];

        $result = $service->buildDefaultConfiguration($available, [], false, true);

        $columns = $result->getColumns();
        $this->assertCount(1, $columns);
        $this->assertSame('name', $columns[0]->getKey());
    }

    public function testBuildDefaultConfigurationSkipsNonVisibleSearchColumns(): void
    {
        $fieldDefinition = $this->makeEmpty(Data::class, [
            'getVisibleSearch' => false,
        ]);

        $service = $this->createService();

        $available = [
            $this->createColumnConfiguration(
                'name',
                ['data_object'],
                null,
                ['fieldDefinition' => $fieldDefinition]
            ),
        ];

        $result = $service->buildDefaultConfiguration($available, [], true);

        $this->assertCount(0, $result->getColumns());
    }

    public function testBuildDefaultConfigurationSkipsColumnsWithoutFieldDefinition(): void
    {
        $service = $this->createService();

        $available = [
            $this->createColumnConfiguration('id', ['system'], null, []),
        ];

        $result = $service->buildDefaultConfiguration($available, [], true, true);

        $this->assertCount(0, $result->getColumns());
    }

    public function testBuildDefaultConfigurationCombinesPredefinedAndSearchColumns(): void
    {
        $fieldDefinition = $this->makeEmpty(Data::class, [
            'getVisibleSearch' => true,
        ]);

        $service = $this->createService();

        $available = [
            $this->createColumnConfiguration('id', ['system']),
            $this->createColumnConfiguration(
                'name',
                ['data_object'],
                null,
                ['fieldDefinition' => $fieldDefinition]
            ),
        ];
        $predefined = [
            ['key' => 'id', 'group' => 'system'],
        ];

        $result = $service->buildDefaultConfiguration($available, $predefined, true);

        $columns = $result->getColumns();
        $this->assertCount(2, $columns);
        $this->assertSame('id', $columns[0]->getKey());
        $this->assertSame('name', $columns[1]->getKey());
    }

    public function testBuildDefaultConfigurationPreservesLocale(): void
    {
        $service = $this->createService();

        $available = [
            $this->createColumnConfiguration('name', ['data_object'], 'de'),
        ];
        $predefined = [
            ['key' => 'name', 'group' => 'data_object'],
        ];

        $result = $service->buildDefaultConfiguration($available, $predefined);

        $this->assertSame('de', $result->getColumns()[0]->getLocale());
    }

    public function testBuildDefaultConfigurationReturnsDefaultMetadata(): void
    {
        $service = $this->createService();

        $result = $service->buildDefaultConfiguration([], []);

        $this->assertSame('Predefined', $result->getName());
        $this->assertSame('Default Grid Configuration', $result->getDescription());
        $this->assertFalse($result->isShareGlobal());
        $this->assertFalse($result->isSaveFilter());
        $this->assertFalse($result->isSetAsFavorite());
        $this->assertSame([], $result->getSharedUsers());
        $this->assertSame([], $result->getSharedRoles());
        $this->assertSame([], $result->getFilter());
    }

    private function createService(
        ?ClassDefinitionResolverInterface $classDefinitionResolver = null,
    ): ConfigurationService {
        return new ConfigurationService(
            $this->makeEmpty(ColumnConfigurationServiceInterface::class),
            $this->makeEmpty(ConfigurationRepositoryInterface::class),
            $this->makeEmpty(ConfigurationHydratorInterface::class),
            $this->makeEmpty(ConfigurationShareServiceInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(DetailedConfigurationHydratorInterface::class),
            $this->makeEmpty(FavoriteServiceInterface::class),
            $classDefinitionResolver ?? $this->makeEmpty(ClassDefinitionResolverInterface::class),
            [],
            [],
            [],
            [],
        );
    }

    private function createColumnConfiguration(
        string $key,
        array $group,
        ?string $locale = null,
        array $config = [],
    ): ColumnConfiguration {
        return new ColumnConfiguration(
            $key,
            $group,
            false,
            false,
            false,
            false,
            false,
            $locale,
            'string',
            'string',
            $config,
        );
    }
}
