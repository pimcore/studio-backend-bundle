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
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
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
}
