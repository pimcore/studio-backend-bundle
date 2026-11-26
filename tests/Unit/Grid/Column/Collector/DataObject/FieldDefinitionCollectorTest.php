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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Column\Collector\DataObject;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Collector\DataObject\FieldDefinitionCollector;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ClassDefinitionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ColumnFieldDefinition;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use function array_map;

/**
 * @internal
 */
final class FieldDefinitionCollectorTest extends Unit
{
    /**
     * The collector is a shared service: a second call for another class must not return
     * the columns collected for the first one.
     */
    public function testGetColumnConfigurationsDoesNotKeepColumnsOfPreviousCall(): void
    {
        $layouts = [
            'car' => $this->createLayout(['name', 'series']),
            'news' => $this->createLayout(['title']),
        ];

        $classDefinitionService = $this->createMock(ClassDefinitionServiceInterface::class);
        $classDefinitionService->method('getFilteredLayoutDefinitions')
            ->willReturnCallback(static fn (string $classId) => $layouts[$classId]);
        $classDefinitionService->method('getClassDefinition')
            ->willReturnCallback(static function (string $classId): ClassDefinition {
                $classDefinition = new ClassDefinition();
                $classDefinition->setName($classId);

                return $classDefinition;
            });

        $columnConfigurationService = $this->createMock(ColumnConfigurationServiceInterface::class);
        $columnConfigurationService->method('buildDataObjectAdapterColumnConfiguration')
            ->willReturnCallback(static fn (ColumnFieldDefinition $definition) => new ColumnConfiguration(
                $definition->getFieldDefinition()->getName(),
                $definition->getGroup(),
                false,
                false,
                false,
                false,
                false,
                null,
                'dataobject.adapter',
                'input',
                []
            ));

        $collector = new FieldDefinitionCollector(
            $classDefinitionService,
            $columnConfigurationService,
            $this->createMock(LoggerInterface::class),
            new InMemoryKeyLocalizationResolver()
        );
        $collector->setFolderId(1);
        $collector->setUser($this->createMock(UserInterface::class));

        $collector->setClassId('car');
        $this->assertSame(['name', 'series'], $this->keys($collector->getColumnConfigurations([])));

        $collector->setClassId('news');
        $this->assertSame(['title'], $this->keys($collector->getColumnConfigurations([])));

        $collector->setClassId('car');
        $this->assertSame(['name', 'series'], $this->keys($collector->getColumnConfigurations([])));
    }

    /**
     * The keys of a store decide their own languages, so a store field that is not localized is
     * offered a language when one of its keys is, and is not when none is.
     */
    public function testAStoreFieldIsLocalizedWhenAnyKeyOfItsStoreIs(): void
    {
        $this->assertSame([true, true], $this->collectLocalizedFlags(fieldLocalized: false, keyLocalized: true));
        $this->assertSame([false, false], $this->collectLocalizedFlags(fieldLocalized: false, keyLocalized: false));
        $this->assertSame([true, true], $this->collectLocalizedFlags(fieldLocalized: true, keyLocalized: false));
    }

    /**
     * What the store field's column is built localized for, and the `localizable` it publishes.
     *
     * @return list<bool>
     */
    private function collectLocalizedFlags(bool $fieldLocalized, bool $keyLocalized): array
    {
        $store = new ClassificationstoreField();
        $store->setName('cs');
        $store->setLocalized($fieldLocalized);
        $layout = new Panel();
        $layout->setChildren([$store]);

        $classDefinitionService = $this->createMock(ClassDefinitionServiceInterface::class);
        $classDefinitionService->method('getFilteredLayoutDefinitions')->willReturn($layout);
        $classDefinitionService->method('getClassDefinition')->willReturnCallback(static function (): ClassDefinition {
            $classDefinition = new ClassDefinition();
            $classDefinition->setName('car');

            return $classDefinition;
        });

        $flags = [];
        $columnConfigurationService = $this->createMock(ColumnConfigurationServiceInterface::class);
        $columnConfigurationService->method('buildDataObjectAdapterColumnConfiguration')
            ->willReturnCallback(
                static function (ColumnFieldDefinition $definition) use (&$flags): ColumnConfiguration {
                    $flags[] = $definition->isLocalized();

                    return new ColumnConfiguration(
                        'cs',
                        $definition->getGroup(),
                        false,
                        false,
                        false,
                        false,
                        $definition->isLocalized(),
                        null,
                        'dataobject.classificationstore',
                        'classificationstore',
                        []
                    );
                }
            );

        $keyLocalization = new InMemoryKeyLocalizationResolver();
        $keyLocalization->flagKeyId(1, $keyLocalized);
        $collector = new FieldDefinitionCollector(
            $classDefinitionService,
            $columnConfigurationService,
            $this->createMock(LoggerInterface::class),
            $keyLocalization
        );
        $collector->setFolderId(1);
        $collector->setUser($this->createMock(UserInterface::class));
        $collector->setClassId('car');

        $columns = $collector->getColumnConfigurations([]);

        return [$flags[0], $columns[0]->isLocalizable()];
    }

    /**
     * @param string[] $fieldNames
     */
    private function createLayout(array $fieldNames): Panel
    {
        $layout = new Panel();
        $layout->setChildren(array_map(static function (string $fieldName): Input {
            $field = new Input();
            $field->setName($fieldName);

            return $field;
        }, $fieldNames));

        return $layout;
    }

    /**
     * @param ColumnConfiguration[] $columns
     *
     * @return string[]
     */
    private function keys(array $columns): array
    {
        return array_map(static fn (ColumnConfiguration $column) => $column->getKey(), $columns);
    }
}
