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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DependencyInjection;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyLocalizationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Collector\DataObject\FieldDefinitionCollector;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\BlockDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\ClassificationStoreResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridService;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\Loader\TaggedIteratorColumnDefinitionLoader;
use ReflectionClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use function array_keys;
use function array_map;
use function basename;
use function dirname;
use function glob;
use function sprintf;

/**
 * Regression test for pimcore/platform-version#475: a column definition class that is not
 * tagged in config/grid.yaml is never offered by the grid, and the collector only logs the
 * dropped field. BlockDefinitionTest constructs the definition directly and therefore cannot
 * see that. This loads the real config/grid.yaml and feeds the tagged definitions through the
 * real loader, so it fails if the registration is dropped again.
 *
 * @internal
 */
final class GridColumnDefinitionWiringTest extends Unit
{
    private const string DATA_OBJECT_DEFINITION_NAMESPACE =
        'Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\\';

    public function testBlockDefinitionIsLoadedAsDataObjectBlockColumnDefinition(): void
    {
        $loader = new TaggedIteratorColumnDefinitionLoader($this->instantiateTaggedDefinitions());
        $columnDefinitions = $loader->loadColumnDefinitions();

        $this->assertArrayHasKey('data-object.block', $columnDefinitions);
        $this->assertInstanceOf(BlockDefinition::class, $columnDefinitions['data-object.block']);
    }

    public function testEveryDataObjectColumnDefinitionIsTagged(): void
    {
        $taggedServiceIds = array_keys($this->loadGridContainer()->findTaggedServiceIds(
            TaggedIteratorColumnDefinitionLoader::COLUMN_DEFINITION_TAG
        ));

        foreach ($this->getDataObjectDefinitionClasses() as $class) {
            $this->assertContains(
                $class,
                $taggedServiceIds,
                sprintf('%s is not tagged in config/grid.yaml and would be silently dropped from the grid', $class)
            );
        }
    }

    /**
     * The three grid services that ask the language question take it from the one bridge, which
     * the Classification Store config registers under its interface; the collector and the
     * Classification Store column resolver are registered by the grid config.
     */
    public function testTheLanguageBridgeIsRegisteredForEveryGridServiceThatAsksIt(): void
    {
        $container = $this->loadGridContainer();
        (new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/config')))
            ->load('classification_store.yaml');

        $this->assertTrue($container->hasDefinition(KeyLocalizationResolverInterface::class));
        $this->assertSame(GridService::class, $container->getDefinition(GridServiceInterface::class)->getClass());
        $this->assertTrue($container->hasDefinition(FieldDefinitionCollector::class));
        $this->assertTrue($container->hasDefinition(ClassificationStoreResolver::class));

        foreach ([FieldDefinitionCollector::class, ClassificationStoreResolver::class, GridService::class] as $class) {
            $types = array_map(
                static fn ($parameter): string => (string) $parameter->getType(),
                (new ReflectionClass($class))->getConstructor()?->getParameters() ?? []
            );
            $this->assertContains(KeyLocalizationResolverInterface::class, $types, $class);
        }
    }

    /**
     * @return list<ColumnDefinitionInterface>
     */
    private function instantiateTaggedDefinitions(): array
    {
        $definitions = [];
        $taggedServiceIds = $this->loadGridContainer()->findTaggedServiceIds(
            TaggedIteratorColumnDefinitionLoader::COLUMN_DEFINITION_TAG
        );

        foreach (array_keys($taggedServiceIds) as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->getConstructor()?->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $definition = $reflection->newInstance();
            $this->assertInstanceOf(ColumnDefinitionInterface::class, $definition);
            $definitions[] = $definition;
        }

        return $definitions;
    }

    /**
     * @return list<class-string>
     */
    private function getDataObjectDefinitionClasses(): array
    {
        $classes = [];
        $pattern = dirname(__DIR__, 3) . '/src/Grid/Column/Definition/DataObject/*Definition.php';

        foreach (glob($pattern) ?: [] as $file) {
            $class = self::DATA_OBJECT_DEFINITION_NAMESPACE . basename($file, '.php');
            if ((new ReflectionClass($class))->isAbstract()) {
                continue;
            }

            $classes[] = $class;
        }

        $this->assertNotEmpty($classes);

        return $classes;
    }

    private function loadGridContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/config'));
        $loader->load('grid.yaml');

        return $container;
    }
}
