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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\EngineCapabilityGuard;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\EngineCapabilityGuardInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueParserInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializer;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\MultiValueAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\MultiValueDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\Loader\TaggedIteratorColumnDefinitionLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;
use function dirname;
use function sprintf;

/**
 * The qualified value of a slot is read through three services where it was read through one, and
 * the two new ones are collaborators of the third: a binding missing from the container is a fatal
 * error on the first object save that touches a classification store, which the unit suites around
 * them cannot see because they construct the three by hand.
 *
 * @internal
 */
final class QualifiedValueWiringTest extends Unit
{
    public function testTheThreeInterfacesAreRegistered(): void
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/config'));
        $loader->load('classification_store.yaml');

        $bindings = [
            QualifiedValueSerializerInterface::class => QualifiedValueSerializer::class,
            QualifiedValueParserInterface::class => QualifiedValueParser::class,
            EngineCapabilityGuardInterface::class => EngineCapabilityGuard::class,
        ];

        foreach ($bindings as $interface => $implementation) {
            $this->assertTrue(
                $container->hasDefinition($interface),
                sprintf('%s is not registered', $interface)
            );
            $this->assertSame($implementation, $container->getDefinition($interface)->getClass());
        }
    }

    public function testTheMultiValueAdapterIsTaggedMappedAndResolvable(): void
    {
        $config = dirname(__DIR__, 3) . '/config';
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator($config));
        $loader->load('data_objects.yaml');

        $this->assertTrue($container->hasDefinition(MultiValueAdapter::class));
        $this->assertArrayHasKey(
            DataAdapterLoaderInterface::ADAPTER_TAG,
            $container->getDefinition(MultiValueAdapter::class)->getTags()
        );

        $adapterConfig = Yaml::parseFile($config . '/pimcore/data_object_adapters.yaml');
        $mapping = $adapterConfig['pimcore_studio_backend']['data_object_data_adapter_mapping'];
        $this->assertSame(['multiValue'], $mapping[MultiValueAdapter::class]);

        $adapter = new MultiValueAdapter(
            $this->createMock(DataAdapterServiceInterface::class),
            $this->createMock(DataServiceInterface::class)
        );
        $adapterLoader = $this->createMock(DataAdapterLoaderInterface::class);
        $adapterLoader->method('loadAdapter')->with(MultiValueAdapter::class)->willReturn($adapter);

        $this->assertInstanceOf(
            SetterDataInterface::class,
            (new DataAdapterService($mapping, $adapterLoader))->tryDataAdapter('multiValue')
        );
    }

    public function testTheMultiValueGridColumnDefinitionIsRegisteredAndTagged(): void
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/config'));
        $loader->load('grid.yaml');

        $this->assertTrue($container->hasDefinition(MultiValueDefinition::class));
        $this->assertArrayHasKey(
            'pimcore.studio_backend.grid_column_definition',
            $container->getDefinition(MultiValueDefinition::class)->getTags()
        );

        $definitions = (new TaggedIteratorColumnDefinitionLoader([new MultiValueDefinition()]))
            ->loadColumnDefinitions();
        $this->assertArrayHasKey('data-object.multiValue', $definitions);
    }
}
