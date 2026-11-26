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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\SubGroup\CollectionController;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\SubGroup\CreateController;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\SubGroup\DeleteController;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\SubGroup\UpdateController;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\SubGroupHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\SubGroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\SubGroupService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\SubGroupServiceInterface;
use ReflectionClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;
use function array_key_exists;
use function dirname;
use function sprintf;

/**
 * The four sub group endpoints are only reachable if the services behind them are registered as
 * their interfaces and the controllers carry their routes, and only documented if every operation
 * has its three translation keys. None of that fails in a way the endpoint tests would notice.
 *
 * @internal
 */
final class SubGroupWiringTest extends Unit
{
    private const string OPERATION_PREFIX = 'classification_store_configuration_sub_group_';

    public function testTheThreeInterfacesAreRegistered(): void
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/config'));
        $loader->load('classification_store.yaml');

        $bindings = [
            SubGroupServiceInterface::class => SubGroupService::class,
            SubGroupRepositoryInterface::class => SubGroupRepository::class,
            SubGroupHydratorInterface::class => SubGroupHydrator::class,
        ];

        foreach ($bindings as $interface => $implementation) {
            $this->assertTrue(
                $container->hasDefinition($interface),
                sprintf('%s is not registered', $interface)
            );
            $this->assertSame($implementation, $container->getDefinition($interface)->getClass());
        }
    }

    public function testTheFourRoutesAreDeclared(): void
    {
        $expected = [
            CollectionController::class => [
                'pimcore_studio_api_cs_configuration_sub_group_collection',
                '/classification-store/configuration/stores/{storeId}/sub-groups',
                'POST',
            ],
            CreateController::class => [
                'pimcore_studio_api_cs_configuration_sub_group_create',
                '/classification-store/configuration/sub-groups/add',
                'POST',
            ],
            UpdateController::class => [
                'pimcore_studio_api_cs_configuration_sub_group_update',
                '/classification-store/configuration/sub-groups/{id}',
                'PUT',
            ],
            DeleteController::class => [
                'pimcore_studio_api_cs_configuration_sub_group_delete',
                '/classification-store/configuration/sub-groups/{id}',
                'DELETE',
            ],
        ];

        foreach ($expected as $controller => [$name, $path, $method]) {
            $arguments = $this->getRouteArguments($controller);

            $this->assertSame($name, $arguments['name']);
            $this->assertSame($path, $arguments[0]);
            $this->assertSame([$method], $arguments['methods']);
        }
    }

    /**
     * `add` is a literal segment under the same prefix as the `{id}` routes, so it needs the
     * priority the group quartet gives its create route or the wildcard swallows it.
     */
    public function testCreateOutranksTheWildcardRoute(): void
    {
        $arguments = $this->getRouteArguments(CreateController::class);

        $this->assertArrayHasKey('priority', $arguments);
        $this->assertSame(10, $arguments['priority']);
    }

    public function testEveryOperationHasItsApiDocTranslations(): void
    {
        $catalogue = Yaml::parseFile(dirname(__DIR__, 3) . '/translations/studio_api_docs.en.yaml');

        foreach (['collection', 'create', 'update', 'delete'] as $operation) {
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

    /**
     * @param class-string $controller
     *
     * @return array<array-key, mixed>
     */
    private function getRouteArguments(string $controller): array
    {
        foreach ((new ReflectionClass($controller))->getMethods() as $method) {
            $attributes = $method->getAttributes(Route::class);

            if ($attributes !== []) {
                return $attributes[0]->getArguments();
            }
        }

        $this->fail(sprintf('%s declares no route', $controller));
    }
}
