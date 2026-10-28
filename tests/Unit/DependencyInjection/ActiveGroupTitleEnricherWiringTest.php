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
use Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection\LayoutDefinitionService as FieldCollectionService;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ObjectBrick\LayoutDefinitionService as ObjectBrickService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ActiveGroupTitleEnricher;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ActiveGroupTitleEnricherInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\LayoutService;
use ReflectionClass;
use ReflectionNamedType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use function dirname;
use function sprintf;

/**
 * The enricher is autowired into the three layout services, so it is only reachable if the interface
 * is registered. An unregistered interface fails at container compile time, far away from the layout
 * endpoints it silently empties.
 *
 * @internal
 */
final class ActiveGroupTitleEnricherWiringTest extends Unit
{
    public function testTheEnricherIsRegisteredAsItsInterface(): void
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/config'));
        $loader->load('classification_store.yaml');

        $this->assertTrue($container->hasDefinition(ActiveGroupTitleEnricherInterface::class));
        $this->assertSame(
            ActiveGroupTitleEnricher::class,
            $container->getDefinition(ActiveGroupTitleEnricherInterface::class)->getClass()
        );
    }

    public function testTheThreeLayoutServicesTakeTheEnricher(): void
    {
        $services = [LayoutService::class, FieldCollectionService::class, ObjectBrickService::class];

        foreach ($services as $service) {
            $this->assertContains(
                ActiveGroupTitleEnricherInterface::class,
                $this->getConstructorTypes($service),
                sprintf('%s does not take the classification store title enricher', $service)
            );
        }
    }

    /**
     * @param class-string $service
     *
     * @return list<string>
     */
    private function getConstructorTypes(string $service): array
    {
        $types = [];

        foreach ((new ReflectionClass($service))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType) {
                $types[] = $type->getName();
            }
        }

        return $types;
    }
}
