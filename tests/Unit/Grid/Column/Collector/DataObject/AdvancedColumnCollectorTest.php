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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Collector\DataObject\AdvancedColumnCollector;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ClassDefinitionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\SystemColumnServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\TransformerLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\SimpleField;
use Pimcore\Bundle\StudioBackendBundle\ObjectBrick\Service\ObjectBrickServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class AdvancedColumnCollectorTest extends Unit
{
    public function testGetColumnConfigurationsExcludesInvisibleSimpleFields(): void
    {
        $visibleField = $this->createInput('name', 'Name', false);
        $invisibleField = $this->createInput('series', 'Series', true);

        $collector = $this->createCollector([$visibleField, $invisibleField]);

        $config = $this->getAdvancedConfig($collector);

        $keys = $this->extractFieldKeys($config->getConfig()['simpleField']);

        $this->assertContains('name', $keys);
        $this->assertNotContains('series', $keys);
    }

    public function testGetColumnConfigurationsExcludesInvisibleRelationFields(): void
    {
        $invisibleRelation = new ManyToManyObjectRelation();
        $invisibleRelation->setName('relatedCars');
        $invisibleRelation->setTitle('Related cars');
        $invisibleRelation->setInvisible(true);

        $collector = $this->createCollector([$invisibleRelation]);

        $config = $this->getAdvancedConfig($collector);

        $this->assertSame([], $config->getConfig()['relationField']);
    }

    private function createInput(string $name, string $title, bool $invisible): Input
    {
        $field = new Input();
        $field->setName($name);
        $field->setTitle($title);
        $field->setInvisible($invisible);

        return $field;
    }

    /**
     * @param array<int, mixed> $children
     */
    private function createCollector(array $children): AdvancedColumnCollector
    {
        $layout = $this->makeEmpty(Layout::class, [
            'getChildren' => $children,
        ]);

        $collector = new AdvancedColumnCollector(
            $this->makeEmpty(ClassDefinitionServiceInterface::class, [
                'getFilteredLayoutDefinitions' => $layout,
            ]),
            $this->makeEmpty(ClassDefinitionRepositoryInterface::class),
            $this->makeEmpty(TransformerLoaderInterface::class, [
                'loadTransformers' => [],
            ]),
            $this->makeEmpty(DefinitionResolverInterface::class),
            $this->makeEmpty(ObjectBrickServiceInterface::class),
            $this->makeEmpty(SystemColumnServiceInterface::class, [
                'getSystemColumnsForDataObjects' => [],
            ]),
        );

        $collector->setClassId('CAR');
        $collector->setFolderId(1);
        $collector->setUser($this->makeEmpty(UserInterface::class));

        return $collector;
    }

    private function getAdvancedConfig(AdvancedColumnCollector $collector): ColumnConfiguration
    {
        $configurations = $collector->getColumnConfigurations([]);

        $this->assertCount(1, $configurations);
        $this->assertSame('advanced', $configurations[0]->getKey());

        return $configurations[0];
    }

    /**
     * @param SimpleField[] $fields
     *
     * @return string[]
     */
    private function extractFieldKeys(array $fields): array
    {
        return array_map(static fn (SimpleField $field) => $field->getKey(), $fields);
    }
}
