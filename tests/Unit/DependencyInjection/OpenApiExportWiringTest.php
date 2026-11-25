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
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Command\GenerateConfigJsonCommand;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiExportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiService;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiServiceInterface;
use ReflectionNamedType;
use ReflectionParameter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use function dirname;

/**
 * The Unit suite never compiles the application container, so a broken service
 * wiring in config/open_api.yaml would only surface at runtime. This loads the
 * real file and asserts that the export alias resolves to a class that
 * actually implements the interface the command asks for.
 *
 * @internal
 */
final class OpenApiExportWiringTest extends Unit
{
    public function testExportInterfaceIsAnAliasOfTheOpenApiService(): void
    {
        $container = $this->loadOpenApiContainer();

        $this->assertTrue($container->hasAlias(OpenApiExportServiceInterface::class));
        $target = (string) $container->getAlias(OpenApiExportServiceInterface::class);
        $this->assertSame(OpenApiServiceInterface::class, $target);

        $class = $container->getDefinition($target)->getClass();
        $this->assertSame(OpenApiService::class, $class);
        $this->assertTrue(is_a($class, OpenApiExportServiceInterface::class, true));
        $this->assertTrue(is_a($class, OpenApiServiceInterface::class, true));
    }

    public function testGenerateConfigJsonCommandIsRegisteredAndItsDependenciesResolve(): void
    {
        $container = $this->loadOpenApiContainer();

        $this->assertTrue($container->hasDefinition(GenerateConfigJsonCommand::class));
        $this->assertArrayHasKey(
            'console.command',
            $container->getDefinition(GenerateConfigJsonCommand::class)->getTags()
        );

        // every constructor dependency must be autowireable from the container
        $constructor = (new \ReflectionClass(GenerateConfigJsonCommand::class))->getConstructor();
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            $this->assertInstanceOf(ReflectionNamedType::class, $type, $this->parameterLabel($parameter));
            if ($type->getName() === OpenApiExportServiceInterface::class) {
                $this->assertTrue($container->hasAlias($type->getName()), $this->parameterLabel($parameter));
            }
        }
    }

    private function parameterLabel(ReflectionParameter $parameter): string
    {
        return 'constructor parameter $' . $parameter->getName();
    }

    private function loadOpenApiContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $configDirectory = dirname(__DIR__, 3) . '/config';
        (new YamlFileLoader($container, new FileLocator($configDirectory)))->load('open_api.yaml');

        return $container;
    }
}
