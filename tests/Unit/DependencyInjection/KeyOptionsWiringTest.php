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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyGroupRelationHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyOptionsResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyOptionsResolverInterface;
use ReflectionClass;
use ReflectionNamedType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Contracts\Service\ResetInterface;
use function dirname;
use function sprintf;

/**
 * The key options resolver is only consulted by the hydrators if it is registered as its interface
 * and they type-hint it, and it only drops its memo between messages if it can be reset.
 *
 * @internal
 */
final class KeyOptionsWiringTest extends Unit
{
    public function testTheInterfaceIsRegistered(): void
    {
        $container = $this->loadContainer();

        $this->assertTrue($container->hasDefinition(KeyOptionsResolverInterface::class));
        $this->assertSame(
            KeyOptionsResolver::class,
            $container->getDefinition(KeyOptionsResolverInterface::class)->getClass()
        );
    }

    public function testBothHydratorsReceiveIt(): void
    {
        $container = $this->loadContainer();

        foreach ([KeyHydrator::class, KeyGroupRelationHydrator::class] as $hydrator) {
            $types = [];
            foreach ((new ReflectionClass($hydrator))->getConstructor()?->getParameters() ?? [] as $parameter) {
                $type = $parameter->getType();
                $types[] = $type instanceof ReflectionNamedType ? $type->getName() : '';
            }

            $this->assertContains(
                KeyOptionsResolverInterface::class,
                $types,
                sprintf('%s does not take the resolver', $hydrator)
            );
        }

        $this->assertTrue($container->hasDefinition(KeyHydrator::class . 'Interface'));
        $this->assertTrue($container->hasDefinition(KeyGroupRelationHydrator::class . 'Interface'));
    }

    public function testTheMemoIsDroppedOnKernelReset(): void
    {
        $this->assertTrue((new ReflectionClass(KeyOptionsResolver::class))->implementsInterface(ResetInterface::class));
    }

    private function loadContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/config'));
        $loader->load('classification_store.yaml');

        return $container;
    }
}
