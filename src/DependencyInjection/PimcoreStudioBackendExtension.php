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
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CsvServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDeleteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\EventSubscriber\CorsSubscriber;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidPathException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidUrlPrefixException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\HubServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Note\Service\NoteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiServiceInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use function sprintf;

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
    private const FIREWALL_PATTERN = '^{prefix}(/.*)?$';

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configPath = __DIR__ . '/../../config';
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Load services and configuration
        $loader = new YamlFileLoader($container, new FileLocator($configPath));

        $files = glob(__DIR__ . '/../../config/*.yaml');
        foreach ($files as $file) {
            $loader->load(basename($file));
        }

        $this->checkValidOpenApiScanPaths($config['open_api_scan_paths']);
        $this->checkValidUrlPrefix($config['url_prefix']);

        $definition = $container->getDefinition(OpenApiServiceInterface::class);
        $definition->setArguments([
            '$routePrefix' => $config['url_prefix'],
            '$openApiScanPaths' => $config['open_api_scan_paths'],
        ]);

        $definition = $container->getDefinition(CorsSubscriber::class);
        $definition->setArgument('$allowedHosts', $config['allowed_hosts_for_cors']);

        $definition = $container->getDefinition(DownloadServiceInterface::class);
        $definition->setArgument('$defaultFormats', $config['asset_default_formats']);

        $definition = $container->getDefinition(ElementDeleteServiceInterface::class);
        $definition->setArgument('$recycleBinThreshold', $config['element_recycle_bin_threshold']);

        $definition = $container->getDefinition(HubServiceInterface::class);
        $definition->setArgument('$cookieLifetime', $config['mercure_settings']['cookie_lifetime']);

        $definition = $container->getDefinition(ZipServiceInterface::class);
        $definition->setArgument('$downloadLimits', $config['asset_download_settings']);

        $definition = $container->getDefinition(CsvServiceInterface::class);
        $definition->setArgument('$defaultDelimiter', $config['csv_settings']['default_delimiter']);

        $definition = $container->getDefinition(ConfigurationServiceInterface::class);
        $definition->setArgument('$predefinedColumns', $config['grid']['asset']['predefined_columns']);

        $definition = $container->getDefinition(NoteServiceInterface::class);
        $definition->setArgument('$noteTypes', $config['notes']['types']);

        $definition = $container->getDefinition(DataAdapterServiceInterface::class);
        $definition->setArgument('$dataAdapters', $config['data_object_data_adapter_mapping']);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $containerConfig = ConfigurationHelper::getConfigNodeFromSymfonyTree(
            $container,
            'pimcore_studio_backend'
        );

        $urlPrefix = rtrim($containerConfig['url_prefix'], '/');

        if (!$container->hasParameter('pimcore_studio_backend.firewall_settings')) {
            $containerConfig['security_firewall']['pattern'] = str_replace(
                '{prefix}',
                $urlPrefix,
                self::FIREWALL_PATTERN
            );
            $container->setParameter('pimcore_studio_backend.firewall_settings', $containerConfig['security_firewall']);
        }

        $container->setParameter('pimcore_studio_backend.url_prefix', $urlPrefix);

        foreach ($containerConfig['mercure_settings'] as $key => $setting) {
            if ($container->hasParameter('pimcore_studio_backend.mercure_settings.' . $key)) {
                continue;
            }
            $container->setParameter(
                'pimcore_studio_backend.mercure_settings.' . $key,
                $containerConfig['mercure_settings'][$key]
            );
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

    /**
     * @throws InvalidUrlPrefixException
     */
    private function checkValidUrlPrefix(string $urlPrefix): void
    {
        if (!str_starts_with($urlPrefix, '/')) {
            throw new InvalidUrlPrefixException(
                sprintf('The URL prefix "%s" must start with a slash.', $urlPrefix)
            );
        }

        // Check if the prefix contains only valid URL path characters
        if (!preg_match('/^\/[a-zA-Z0-9\-_\/]*$/', $urlPrefix)) {
            throw new InvalidUrlPrefixException(
                sprintf('The URL prefix "%s" must only contain valid URL characters.', $urlPrefix)
            );
        }
    }
}
