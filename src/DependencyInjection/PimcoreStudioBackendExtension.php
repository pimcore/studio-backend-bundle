<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DependencyInjection;

use Exception;
use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Pimcore\Bundle\StudioBackendBundle\EventSubscriber\CorsSubscriber;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidPathException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiServiceInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */

/**
 * @internal
 */
class PimcoreStudioBackendExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Load services and configuration
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        $loader->load('assets.yaml');
        $loader->load('authorization.yaml');
        $loader->load('data_index.yaml');
        $loader->load('data_index_filters.yaml');
        $loader->load('data_objects.yaml');
        $loader->load('dependencies.yaml');
        $loader->load('element_workflow.yaml');
        $loader->load('event_subscribers.yaml');
        $loader->load('factories.yaml');
        $loader->load('filters.yaml');
        $loader->load('icon.yaml');
        $loader->load('notes.yaml');
        $loader->load('open_api.yaml');
        $loader->load('patcher.yaml');
        $loader->load('properties.yaml');
        $loader->load('resolver.yaml');
        $loader->load('schedules.yaml');
        $loader->load('security.yaml');
        $loader->load('services.yaml');
        $loader->load('settings.yaml');
        $loader->load('translation.yaml');
        $loader->load('updater.yaml');
        $loader->load('users.yaml');
        $loader->load('versions.yaml');

        $this->checkValidOpenApiScanPaths($config['open_api_scan_paths']);
        $definition = $container->getDefinition(OpenApiServiceInterface::class);
        $definition->setArgument('$openApiScanPaths', $config['open_api_scan_paths']);

        $definition = $container->getDefinition(CorsSubscriber::class);
        $definition->setArgument('$allowedHosts', $config['allowed_hosts_for_cors']);
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('pimcore_studio_backend.firewall_settings')) {
            $containerConfig = ConfigurationHelper::getConfigNodeFromSymfonyTree($container, 'pimcore_studio_backend');
            $container->setParameter('pimcore_studio_backend.firewall_settings', $containerConfig['security_firewall']);
        }
    }

    /**
     * @throws InvalidPathException
     */
    private function checkValidOpenApiScanPaths(array $config): void
    {
        foreach ($config as $path) {
            if (!is_dir($path)) {
                throw new InvalidPathException(
                    sprintf(
                        'The path "%s" is not a valid directory.',
                        $path
                    )
                );
            }
        }
    }
}
