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

namespace Pimcore\Bundle\StudioBackendBundle\DependencyInjection;

use Exception;
use InvalidArgumentException;
use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentTypeServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDeleteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\EventSubscriber\CorsSubscriber;
use Pimcore\Bundle\StudioBackendBundle\EventSubscriber\RateLimitSubscriber;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidHostException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidUrlPrefixException;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\CsvExportService;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\XlsxExportService;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Collector\DataObject\FieldDefinitionCollector;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UrlServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\DataAdapterServiceInterface as MetadataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Note\Service\NoteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\EmailChannel;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\Messenger\SendNotificationEmailHandler;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\ElementTreeWidgetConfigRepository;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\PerspectiveConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\WidgetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\WidgetValidationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\PatAuthenticator;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Repository\SettingRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\AdminLanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Twig\Initializers\SandboxExtensionInitializerInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\KeyBindingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\MailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Config\ConfigKeyMapper;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
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
    private const string FIREWALL_PATTERN = '^{prefix}(/.*)?$';

    private const string MCP_FIREWALL_PATTERN = '^/pimcore-mcp/';

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Load services and configuration
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        $files = glob(__DIR__ . '/../../config/*.yaml');
        foreach ($files as $file) {
            $loader->load(
                basename($file)
            );
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->checkValidUrlPrefix($config['url_prefix']);
        $this->checkValidServers($config['open_api_servers']);

        $definition = $container->getDefinition(OpenApiServiceInterface::class);
        $definition->setArguments([
            '$routePrefix' => $config['url_prefix'],
            '$openApiScanPaths' => $config['open_api_scan_paths'],
            '$openApiServers' => $config['open_api_servers'],
        ]);

        $definition = $container->getDefinition(CorsSubscriber::class);
        $definition->setArgument('$allowedHosts', $config['allowed_hosts_for_cors']);

        $definition = $container->getDefinition(RateLimitSubscriber::class);
        $definition->setArgument('$enabled', $config['rate_limiting']['enabled']);

        $definition = $container->getDefinition(DownloadServiceInterface::class);
        $definition->setArgument('$defaultFormats', $config['asset_default_formats']);

        $definition = $container->getDefinition(ElementDeleteServiceInterface::class);
        $definition->setArgument('$recycleBinThreshold', $config['element_recycle_bin_threshold']);

        $definition = $container->getDefinition(ZipServiceInterface::class);
        $definition->setArgument('$downloadLimits', $config['asset_download_settings']);

        $definition = $container->getDefinition(CsvExportService::class);
        $definition->setArgument('$defaultDelimiter', $config['csv_settings']['default_delimiter']);

        $definition = $container->getDefinition(XlsxExportService::class);
        $definition->setArgument('$defaultDelimiter', $config['csv_settings']['default_delimiter']);

        $definition = $container->getDefinition(ConfigurationServiceInterface::class);
        $definition->setArgument('$assetPredefinedColumns', $config['grid']['asset']['predefined_columns']);

        $definition = $container->getDefinition(ConfigurationServiceInterface::class);

        $definition->setArgument(
            '$assetSearchPredefinedColumns',
            $config['search_grid']['asset']['predefined_columns']
        );

        $definition->setArgument(
            '$dataObjectSearchPredefinedColumns',
            $config['search_grid']['data_object']['predefined_columns']
        );

        $definition = $container->getDefinition(FieldDefinitionCollector::class);
        $definition->setArgument('$skipFieldTypes', $config['grid']['data_object']['skip_field_types']);

        $definition = $container->getDefinition(ConfigurationServiceInterface::class);
        $definition->setArgument('$dataObjectPredefinedColumns', $config['grid']['data_object']['predefined_columns']);

        $definition = $container->getDefinition(NoteServiceInterface::class);
        $definition->setArgument('$noteTypes', $config['notes']['types']);

        $definition = $container->getDefinition(MetadataAdapterServiceInterface::class);
        $definition->setArgument('$studioAdapters', $config['asset_metadata_adapter_mapping']);

        $definition = $container->getDefinition(DataAdapterServiceInterface::class);
        $definition->setArgument('$dataAdapters', $config['data_object_data_adapter_mapping']);

        $definition = $container->getDefinition(DocumentTypeServiceInterface::class);
        $definition->setArgument('$typeAdapters', $config['document_type_adapter_mapping']);

        $definition = $container->getDefinition(KeyBindingServiceInterface::class);
        $definition->setArgument('$defaultKeyBindings', $config['user']['default_key_bindings']);

        $definition = $container->getDefinition(MailServiceInterface::class);
        $definition->setArgument('$fromEmail', $config['studio_from_default_email']);

        $definition = $container->getDefinition(SendNotificationEmailHandler::class);
        $definition->setArgument('$fromEmail', $config['studio_from_default_email']);
        $definition->setArgument('$template', $config['notifications']['email']['template']);

        // Studio UI base path for the email deep links; default it since studio-ui isn't a hard dependency.
        if (!$container->hasParameter('pimcore_studio_ui.url_path')) {
            $container->setParameter('pimcore_studio_ui.url_path', '/pimcore-studio');
        }
        $container->getDefinition(EmailChannel::class)
            ->setArgument('$studioPath', '%pimcore_studio_ui.url_path%');

        $definition = $container->getDefinition(WidgetServiceInterface::class);
        $definition->setArgument('$widgetTypes', $config['widget_types']);

        $definition = $container->getDefinition(WidgetValidationServiceInterface::class);
        $definition->setArgument('$widgetTypes', $config['widget_types']);

        $defaultPerspective = ConfigKeyMapper::convertKeysForApp(
            $this->getParsedConfig(
                __DIR__ . '/../../config/pimcore/default_perspective.yaml'
            )
        );

        $definition = $container->getDefinition(ElementTreeWidgetConfigRepository::class);
        $definition->setArguments([
            '$defaultPerspective' => $defaultPerspective,
            '$widgetConfigurations' => $config[Configuration::TREE_WIDGETS_NODE],
            '$storageConfig' => $config['config_location'][Configuration::TREE_WIDGETS_NODE],
        ]);

        $definition = $container->getDefinition(PerspectiveConfigRepositoryInterface::class);
        $definition->setArguments([
            '$defaultPerspective' => $defaultPerspective,
            '$perspectiveConfigurations' => $config[Configuration::PERSPECTIVES_NODE],
            '$storageConfig' => $config['config_location'][Configuration::PERSPECTIVES_NODE],
        ]);

        $definition = $container->getDefinition(UrlServiceInterface::class);
        $definition->setArguments([
            '$serverSideUrl' => $config['mercure_settings']['hub_url_server'],
            '$clientSideUrl' => $config['mercure_settings']['hub_url_client'],
        ]);

        $container->setParameter(
            'pimcore_studio_backend.gdpr_data_extractor',
            $config['gdpr_data_extractor']
        );

        $container->setParameter(
            'pimcore_studio_backend.notifications.channels',
            $config['notifications']['channels']
        );

        $definition = $container->getDefinition(SettingRepositoryInterface::class);
        $definition->setArguments([
            '$adminConfig' => [
                Configuration::ADMIN_SETTINGS_NODE => $config[Configuration::ADMIN_SETTINGS_NODE],
            ],
            '$storageConfig' => $config['config_location'][Configuration::ADMIN_SETTINGS_NODE],
        ]);

        $this->populateTwigSandboxExtension($config, $container);

        // MCP authentication token map
        $mcpTokenMap = $config['mcp']['authentication']['tokens'] ?? [];
        $container->setParameter('pimcore_studio_backend.mcp.token_map', $mcpTokenMap);

        $definition = $container->getDefinition(PatAuthenticator::class);
        $definition->setArgument('$tokenMap', $mcpTokenMap);

        $definition = $container->getDefinition(AdminLanguageServiceInterface::class);
        $definition->setArgument('$translationsPath', $config['translations']['path']);
        $definition->setArgument('$defaultTranslationsPath', '%translator.default_path%');

        $container->setParameter(
            'pimcore_studio_backend.translations.auto_create_missing_keys',
            $config['translations']['auto_create_missing_keys']
        );
    }

    /**
     * @throws Exception
     */
    public function prepend(ContainerBuilder $container): void
    {
        // Load bundles
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config/prepend'));
        if ($container->hasExtension('pimcore_application_logger')) {
            $loader->load('bundle_application_logger.yaml');
        }
        if ($container->hasExtension('pimcore_custom_reports')) {
            $loader->load('bundle_custom_reports.yaml');
        }
        if ($container->hasExtension('pimcore_seo')) {
            $loader->load('bundle_seo.yaml');
        }
        $loader->load('rate_limiter.yaml');
        // Routes the email channel's send message onto the pimcore_core transport (see the file).
        $loader->load('notification.yaml');

        $containerConfig = ConfigurationHelper::getConfigNodeFromSymfonyTree(
            $container,
            Configuration::ROOT_NODE
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

        // Default MCP token map (overwritten in load() with actual config)
        if (!$container->hasParameter('pimcore_studio_backend.mcp.token_map')) {
            $container->setParameter('pimcore_studio_backend.mcp.token_map', []);
        }

        // MCP firewall settings (separate firewall for /pimcore-mcp/ routes)
        if (!$container->hasParameter('pimcore_studio_backend.mcp_firewall_settings')) {
            $container->setParameter('pimcore_studio_backend.mcp_firewall_settings', [
                'pattern' => self::MCP_FIREWALL_PATTERN,
                'user_checker' => 'Pimcore\Security\User\UserChecker',
                'provider' => 'pimcore_studio_backend',
                'stateless' => true,
                // Throttles guesses at MCP bearer credentials. PatAuthenticator builds its
                // UserBadge before performing any lookup, so LoginThrottlingListener
                // (CheckPassportEvent, priority 2080) can reject a throttled client before
                // any database work happens.
                //
                // A limiter service is supplied deliberately: without it Symfony builds a
                // DefaultLoginRateLimiter whose derived per-IP tier is peeked by every
                // client on an address, so guesses against one credential can push an
                // unrelated valid credential into a 429. McpLoginRateLimiter has a single
                // tier and exempts anything that resolves to a user. max_attempts and
                // interval are ignored when limiter is set - tune limiter.studio_mcp_login
                // via framework.rate_limiter instead.
                'login_throttling' => [
                    'limiter' => 'Pimcore\Bundle\StudioBackendBundle\Security\RateLimiter\McpLoginRateLimiterInterface',
                ],
                'custom_authenticators' => [
                    'Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\SessionBridgeAuthenticator',
                    'Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\McpAccessTokenAuthenticator',
                    'Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\PatAuthenticator',
                ],
            ]);
        }

        $processedConfig = $this->processConfiguration(
            new Configuration(),
            $container->getExtensionConfig(Configuration::ROOT_NODE)
        );

        foreach ($processedConfig['mercure_settings'] as $key => $setting) {
            if ($container->hasParameter('pimcore_studio_backend.mercure_settings.' . $key)) {
                continue;
            }

            $container->setParameter(
                'pimcore_studio_backend.mercure_settings.' . $key,
                $processedConfig['mercure_settings'][$key]
            );
        }

        $this->prependCustomConfig($container, $containerConfig, Configuration::PERSPECTIVES_NODE);
        $this->prependCustomConfig($container, $containerConfig, Configuration::TREE_WIDGETS_NODE);
        $this->prependCustomConfig($container, $containerConfig, Configuration::ADMIN_SETTINGS_NODE);
    }

    /**
     * @throws InvalidHostException
     */
    private function checkValidServers(array $servers): void
    {
        if (empty($servers)) {
            return;
        }

        foreach ($servers as $serverUrl) {
            if (!filter_var($serverUrl['url'], FILTER_VALIDATE_URL)) {
                throw new InvalidHostException(
                    sprintf('The server URL "%s" is not a valid URL.', $serverUrl)
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

    /**
     * @throws Exception
     */
    private function prependCustomConfig(ContainerBuilder $container, array $containerConfig, string $node): void
    {
        $configLocation = $containerConfig['config_location'][$node];
        $configDir = $configLocation['write_target']['options']['directory'];

        $configLoader = new YamlFileLoader(
            $container,
            new FileLocator($configDir)
        );

        $configs = ConfigurationHelper::getSymfonyConfigFiles($configDir);
        foreach ($configs as $config) {
            $configLoader->load($config);
        }
    }

    private function populateTwigSandboxExtension(array $config, ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(SandboxExtensionInitializerInterface::class);

        $definition->setArgument(
            '$allowedTags',
            $config['twig']['sandbox_security_policy']['tags']
        );
        $definition->setArgument(
            '$allowedFilters',
            $config['twig']['sandbox_security_policy']['filters']
        );
        $definition->setArgument(
            '$allowedFunctions',
            $config['twig']['sandbox_security_policy']['functions']
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getParsedConfig(string $fileLocation): array
    {
        try {
            return Yaml::parseFile($fileLocation);
        } catch (ParseException $e) {
            throw new InvalidArgumentException(
                sprintf('The file "%s" does not contain valid YAML.', $fileLocation),
                0,
                $e
            );
        }
    }
}
