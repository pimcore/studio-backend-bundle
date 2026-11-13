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
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Command\ApplyStoreCommand;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Command\ExportStoreCommand;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\Store\ApplyController;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\Store\ExportController;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\Store\ImportController;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactBuilder;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactBuilderInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryMapper;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryMapperInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParserInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\Reconciler;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ReconcilerInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\StoreTransferService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\StoreTransferServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use ReflectionClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Yaml\Yaml;
use function array_key_exists;
use function dirname;
use function sprintf;

/**
 * Export and apply have to be reachable the same way from both sides, and an endpoint that replaces
 * a whole store's configuration has to be guarded. Neither fails in a way the services' own tests
 * would notice.
 *
 * @internal
 */
final class ConfigurationTransferWiringTest extends Unit
{
    private const string OPERATION_PREFIX = 'classification_store_configuration_store_';

    private const string COMMAND_TAG = 'console.command';

    public function testTheTransferServicesAreRegisteredAsTheirInterfaces(): void
    {
        $container = $this->loadContainer();

        $bindings = [
            ArtifactEntryMapperInterface::class => ArtifactEntryMapper::class,
            ArtifactBuilderInterface::class => ArtifactBuilder::class,
            ArtifactParserInterface::class => ArtifactParser::class,
            ReconcilerInterface::class => Reconciler::class,
            StoreTransferServiceInterface::class => StoreTransferService::class,
        ];

        foreach ($bindings as $interface => $implementation) {
            $this->assertTrue(
                $container->hasDefinition($interface),
                sprintf('%s is not registered', $interface)
            );
            $this->assertSame($implementation, $container->getDefinition($interface)->getClass());
        }
    }

    /**
     * A command the container does not know about is one a deployment cannot run, which is the
     * whole point of having it alongside the endpoints.
     */
    public function testBothCommandsAreRegisteredAsConsoleCommands(): void
    {
        $container = $this->loadContainer();

        foreach ([ExportStoreCommand::class, ApplyStoreCommand::class] as $command) {
            $this->assertTrue(
                $container->hasDefinition($command),
                sprintf('%s is not registered', $command)
            );
            $this->assertArrayHasKey(
                self::COMMAND_TAG,
                $container->getDefinition($command)->getTags(),
                sprintf('%s is not tagged %s', $command, self::COMMAND_TAG)
            );
        }
    }

    public function testBothCommandsResolveByTheBundlesOwnNamespace(): void
    {
        $expected = [
            ExportStoreCommand::class => 'studio-backend-bundle:classificationstore:export',
            ApplyStoreCommand::class => 'studio-backend-bundle:classificationstore:apply',
        ];

        foreach ($expected as $command => $name) {
            $attributes = (new ReflectionClass($command))->getAttributes(AsCommand::class);

            $this->assertCount(1, $attributes, sprintf('%s declares no command name', $command));
            $this->assertSame($name, $attributes[0]->getArguments()['name']);
        }
    }

    public function testTheTwoRoutesAreDeclared(): void
    {
        $expected = [
            ExportController::class => [
                'pimcore_studio_api_cs_configuration_store_export',
                '/classification-store/configuration/stores/{id}/export',
                'GET',
            ],
            ImportController::class => [
                'pimcore_studio_api_cs_configuration_store_import',
                '/classification-store/configuration/stores/{id}/import',
                'POST',
            ],
            ApplyController::class => [
                'pimcore_studio_api_cs_configuration_store_apply',
                '/classification-store/configuration/stores/apply',
                'POST',
            ],
        ];

        foreach ($expected as $controller => [$name, $path, $method]) {
            $arguments = $this->getAttributeArguments($controller, Route::class);

            $this->assertSame($name, $arguments['name']);
            $this->assertSame($path, $arguments[0]);
            $this->assertSame([$method], $arguments['methods']);
        }
    }

    /**
     * The import replaces a whole store's configuration, so an unguarded one would be a security
     * gap rather than a documentation gap. Both halves carry the same permission.
     */
    public function testBothEndpointsAreGuardedByTheClassificationStorePermission(): void
    {
        foreach ([ExportController::class, ImportController::class, ApplyController::class] as $controller) {
            $arguments = $this->getAttributeArguments($controller, IsGranted::class);

            $this->assertSame(UserPermissions::CLASSIFICATION_STORE->value, $arguments[0]);
        }
    }

    /**
     * The operation id is what the generated OpenAPI document carries the route under.
     */
    public function testTheApplyRouteIsDocumentedUnderItsOperationId(): void
    {
        $arguments = $this->getAttributeArguments(ApplyController::class, Post::class);

        $this->assertSame(self::OPERATION_PREFIX . 'apply', $arguments['operationId']);
        $this->assertStringEndsWith('/classification-store/configuration/stores/apply', $arguments['path']);
    }

    public function testEveryOperationHasItsApiDocTranslations(): void
    {
        $catalogue = Yaml::parseFile(dirname(__DIR__, 3) . '/translations/studio_api_docs.en.yaml');

        foreach (['export', 'import', 'apply'] as $operation) {
            foreach (['_description', '_summary', '_success_response'] as $suffix) {
                $key = self::OPERATION_PREFIX . $operation . $suffix;

                $this->assertTrue(
                    array_key_exists($key, $catalogue),
                    sprintf('%s is missing from the studio_api_docs catalogue', $key)
                );
                $this->assertNotSame('', trim((string) $catalogue[$key]));
            }
        }
    }

    private function loadContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/config'));
        $loader->load('classification_store.yaml');

        return $container;
    }

    /**
     * @param class-string $controller
     * @param class-string $attribute
     *
     * @return array<array-key, mixed>
     */
    private function getAttributeArguments(string $controller, string $attribute): array
    {
        foreach ((new ReflectionClass($controller))->getMethods() as $method) {
            $attributes = $method->getAttributes($attribute);

            if ($attributes !== []) {
                return $attributes[0]->getArguments();
            }
        }

        $this->fail(sprintf('%s declares no %s', $controller, $attribute));
    }
}
