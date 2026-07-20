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

use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidHostException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\WidgetTypes;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Repository\SettingRepository;
use Pimcore\Bundle\StudioBackendBundle\Util\Config\ConfigKeyMapper;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\DownloadLimits;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\ResizeModes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function is_array;
use function is_int;
use function is_null;
use function is_string;
use function sprintf;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    public const string ROOT_NODE = 'pimcore_studio_backend';

    public const string PERSPECTIVES_NODE = 'studio_perspectives';

    public const string ADMIN_SETTINGS_NODE = 'admin_settings';

    public const string TREE_WIDGETS_NODE = WidgetTypes::ELEMENT_TREE->value . '_widgets';

    private const string WIDGETS_ARRAY_VALUE_ERROR = 'Each widget id value must be a string.';

    private const string PERMISSION_ARRAY_VALUE_ERROR = 'Each permission value must be a boolean.';

    /**
     * {@inheritdoc}
     *
     * @throws InvalidHostException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);

        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();
        $this->addUrlPrefix($rootNode);
        $this->addOpenApiScanPathsNode($rootNode);
        $this->addApiTokenNode($rootNode);
        $this->addAllowedHostsForCorsNode($rootNode);
        $this->addSecurityFirewall($rootNode);
        $this->addDefaultAssetFormats($rootNode);
        $this->addRecycleBinThreshold($rootNode);
        $this->addMercureConfiguration($rootNode);
        $this->addAssetDownloadLimits($rootNode);
        $this->addCsvSettings($rootNode);
        $this->addGridConfiguration($rootNode);
        $this->addSearchGridConfiguration($rootNode);
        $this->addNoteTypes($rootNode);
        $this->addNotificationsNode($rootNode);
        $this->addClassMapping($rootNode, 'asset_metadata_adapter_mapping');
        $this->addClassMapping($rootNode, 'data_object_data_adapter_mapping');
        $this->addClassMapping($rootNode, 'document_type_adapter_mapping');
        $this->addUserNode($rootNode);
        $this->addServerNode($rootNode);
        $this->addWidgetTypesNode($rootNode);
        $this->addPerspectivesConfigurationNode($rootNode);
        $this->addElementTreeWidgetConfigurationNode($rootNode);
        $this->addDefaultFromEmail($rootNode);
        $this->addGdprDataExtractorNode($rootNode);
        $this->addAdminSettingsNode($rootNode);
        $this->addMcpNode($rootNode);
        $this->addOAuthNode($rootNode);
        $this->addRateLimitingNode($rootNode);
        $this->addTranslation($rootNode);
        $rootNode->append($this->addTwigSandboxNode());

        ConfigurationHelper::addConfigLocationWithWriteTargetNodes(
            $rootNode,
            [
                self::TREE_WIDGETS_NODE =>
                    PIMCORE_CONFIGURATION_DIRECTORY . '/' . self::TREE_WIDGETS_NODE,
                self::PERSPECTIVES_NODE =>
                    PIMCORE_CONFIGURATION_DIRECTORY . '/' . self::PERSPECTIVES_NODE,
                self::ADMIN_SETTINGS_NODE =>
                    PIMCORE_CONFIGURATION_DIRECTORY . '/' . SettingRepository::SCOPE,
            ],
            ['read_target']
        );

        return $treeBuilder;
    }

    private function addUrlPrefix(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->scalarNode('url_prefix')
            ->defaultValue('/pimcore-studio/api')
        ->end();
    }

    private function addOpenApiScanPathsNode(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('open_api_scan_paths')
               ->prototype('scalar')->end()
               ->end()
           ->end();
    }

    private function addApiTokenNode(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('api_token')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode('lifetime')
                        ->defaultValue(3600)
                    ->end()
                ->end()
            ->end();
    }

    private function addAllowedHostsForCorsNode(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('allowed_hosts_for_cors')
                ->prototype('scalar')->end()
                ->validate()
                ->always(
                    /**
                     * @throws InvalidHostException
                     */ function ($hosts) {
                        foreach ($hosts as $host) {
                            if (!filter_var($host)) {
                                throw new InvalidHostException(
                                    sprintf(
                                        'The host "%s" is not a valid url.',
                                        $host
                                    )
                                );
                            }
                        }

                        return $hosts;
                    })
                ->end()
            ->end();
    }

    public function addSecurityFirewall(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->variableNode('security_firewall')->end()
            ->end();
    }

    private function addDefaultAssetFormats(ArrayNodeDefinition $node): void
    {
        $node->children()
                ->arrayNode('asset_default_formats')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->enumNode('resize_mode')
                                ->values(ResizeModes::ALLOWED_MODES)
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->integerNode('width')->isRequired()->end()
                            ->integerNode('dpi')->isRequired()->end()
                            ->enumNode('format')
                                ->values([MimeTypes::JPEG->value, MimeTypes::PNG->value])
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->integerNode('quality')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addRecycleBinThreshold(ArrayNodeDefinition $node): void
    {
        $node->children()
                ->integerNode('element_recycle_bin_threshold')
                    ->defaultValue(100)
                ->end()
            ->end();
    }

    private function addGridConfiguration(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('grid')
                ->children()
                    ->arrayNode('asset')
                        ->children()
                            ->arrayNode('predefined_columns')
                                ->performNoDeepMerging()
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('group')->end()
                                        ->scalarNode('key')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('data_object')
                       ->children()
                           ->arrayNode('predefined_columns')
                               ->performNoDeepMerging()
                               ->arrayPrototype()
                                   ->children()
                                       ->scalarNode('group')->end()
                                       ->scalarNode('key')->end()
                                   ->end()
                               ->end()
                           ->end()
                            ->arrayNode('skip_field_types')
                                ->prototype('scalar')->end()
                                ->defaultValue([])
                            ->end()
                       ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addSearchGridConfiguration(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('search_grid')
                ->children()
                    ->arrayNode('asset')
                        ->children()
                            ->arrayNode('predefined_columns')
                                ->performNoDeepMerging()
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('group')->end()
                                        ->scalarNode('key')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('data_object')
                        ->children()
                            ->arrayNode('predefined_columns')
                                ->performNoDeepMerging()
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('group')->end()
                                        ->scalarNode('key')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addMercureConfiguration(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('mercure_settings')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('hub_url_server')
                        ->info(
                            'The url to the mercure hub for the server.' .
                            'This can also be the docker container name '.
                            '(e.g., http://mercure/.well-known/mercure). ' .
                            'If it is not set, default will be set to ' .
                            '"http(s)://<YOUR_PIMCORE_MAIN_DOMAIN>/hub/.well-known/mercure".'
                        )
                        ->defaultNull()
                    ->end()
                    ->scalarNode('hub_url_client')
                        ->info('The url to the mercure hub for the (frontend) client. ' .
                            'If it is not set, the default will be set to ' .
                            '"http(s)://<YOUR_CURRENT_PIMCORE_HOST>/hub". ' .
                            'It is possible to use "<PIMCORE_SCHEMA_HOST>" ' .
                            'as a placeholder for the current schema and host if path to mercure should be different.'
                        )
                        ->defaultNull()
                    ->end()
                    ->scalarNode('jwt_key')
                        ->info('The key used to sign the JWT token. Must be longer than 256 bits.')
                        ->isRequired()
                        ->defaultValue('some-secret-default')
                    ->end()
                     ->integerNode('cookie_lifetime')
                        ->info('Lifetime of the mercure cookie in seconds. Default is one hour.')
                        ->defaultValue(3600)
                    ->end()
                     ->scalarNode('jwt_cookie_host')
                        ->info('Domain where to set the Mercure auth cookie, e.g. ".example.com".')
                        ->defaultNull()
                    ->end()
                     ->booleanNode('jwt_cookie_strictness')
                        ->info('If true, use SameSite=Strict; if false, use SameSite=None.')
                        ->defaultTrue()
                    ->end()
                ->end()
            ->end();
    }

    private function addAssetDownloadLimits(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('asset_download_settings')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode(DownloadLimits::MAX_ZIP_FILE_SIZE->value)
                        ->info('The maximum size of all assets together that can be downloaded in bytes.')
                        ->defaultValue(5 * 1024 * 1024 * 1024)
                    ->end()
                    ->integerNode(DownloadLimits::MAX_ZIP_FILE_AMOUNT->value)
                        ->info('The maximum amount of assets that can be downloaded at once.')
                        ->defaultValue(1000)
                    ->end()
                ->end()
            ->end();
    }

    private function addCsvSettings(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('csv_settings')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('default_delimiter')
                        ->info('Default delimiter to be used for csv operations.')
                        ->defaultValue(';')
                    ->end()
                ->end()
            ->end();
    }

    /**
     * The administrator's only lever: switching a channel off installation-wide. Deliberately
     * not a per-type matrix — a disabled channel disappears from the API entirely.
     */
    private function addNotificationsNode(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('notifications')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('channels')
                        ->info(
                            'Enable or disable notification delivery channels contributed by ' .
                            'bundles, keyed by channel name.'
                        )
                        ->useAttributeAsKey('name')
                        ->arrayPrototype()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')
                                    ->defaultTrue()
                                    ->info('Disabling removes the channel from the preferences screen.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('email')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('template')
                                ->defaultValue('@PimcoreStudioBackend/notification/email.html.twig')
                                ->cannotBeEmpty()
                                ->info(
                                    'Twig template for notification emails. ' .
                                    'Receives: title, message, link, name, locale.'
                                )
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addNoteTypes(ArrayNodeDefinition $node): void
    {
        $defaultOptions = ['content', 'seo', 'warning', 'notice'];

        $node->children()
        ->arrayNode('notes')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('types')
                    ->beforeNormalization()
                        ->always(fn (mixed $v) => is_array($v)
                            ? ConfigKeyMapper::convertKeysForApp($v)
                            : $v)
                    ->end()
                    ->info('List all note types for asset, document, and data-object.')
                    ->normalizeKeys(false)
                    ->children()
                        ->arrayNode(ElementTypes::TYPE_ASSET)
                            ->prototype('scalar')->end()
                            ->defaultValue($defaultOptions)
                        ->end()
                        ->arrayNode(ElementTypes::TYPE_DOCUMENT)
                            ->prototype('scalar')->end()
                            ->defaultValue($defaultOptions)
                        ->end()
                        ->arrayNode(ElementTypes::TYPE_DATA_OBJECT)
                            ->prototype('scalar')->end()
                            ->defaultValue($defaultOptions)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addClassMapping(ArrayNodeDefinition $node, string $name): void
    {
        $node->children()
            ->arrayNode($name)
                ->useAttributeAsKey('class')
                ->arrayPrototype()
                    ->scalarPrototype()
                    ->end()
                ->end()
            ->end();
    }

    private function addUserNode(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('user')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('default_key_bindings')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('key')->isRequired()->end()
                            ->scalarNode('action')->isRequired()->end()
                            ->scalarNode('alt')->defaultFalse()->end()
                            ->scalarNode('ctrl')->defaultFalse()->end()
                            ->scalarNode('shift')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addServerNode(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('open_api_servers')
                    ->arrayPrototype() // Allows multiple server entries
                        ->children()
                            ->scalarNode('url')
                                ->isRequired() // Ensure each server has a URL
                                ->info('The URL to the server.')
                            ->end()
                            ->scalarNode('description')
                                ->isRequired() // Ensure each server has a description
                                ->info('A description of the server.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addWidgetTypesNode(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('widget_types')
                ->prototype('scalar')->end()
            ->end()
        ->end();
    }

    private function addPerspectivesConfigurationNode(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode(self::PERSPECTIVES_NODE)
                ->defaultValue([])
                ->useAttributeAsKey('id')
                ->arrayPrototype()
                    ->beforeNormalization()
                        ->always(fn (mixed $v) => is_array($v)
                            ? ConfigKeyMapper::convertKeysForConfig($v)
                            : $v)
                    ->end()
                    ->children()
                        ->scalarNode('name')
                            ->isRequired()
                        ->end()
                        ->arrayNode('icon')
                            ->isRequired()
                            ->beforeNormalization()
                                ->ifNull()
                                ->then(fn () => [])
                            ->end()
                            ->validate()
                                ->ifTrue(fn ($icon) => is_array($icon) &&
                                    (isset($icon['type']) xor isset($icon['value']))
                                )
                                ->thenInvalid('Both "type" and "value" must be defined together in "icon".')
                            ->end()
                            ->children()
                                ->enumNode('type')
                                    ->values(ElementIconTypes::values())
                                ->end()
                                ->scalarNode('value')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('widgets_left')
                            ->useAttributeAsKey('id')
                            ->scalarPrototype()
                                ->validate()
                                    ->ifTrue(fn ($value) => !is_string($value))
                                    ->thenInvalid(self::WIDGETS_ARRAY_VALUE_ERROR)
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('widgets_right')
                            ->useAttributeAsKey('id')
                            ->scalarPrototype()
                                ->validate()
                                    ->ifTrue(fn ($value) => !is_string($value))
                                    ->thenInvalid(self::WIDGETS_ARRAY_VALUE_ERROR)
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('widgets_bottom')
                            ->useAttributeAsKey('id')
                            ->scalarPrototype()
                                ->validate()
                                    ->ifTrue(fn ($value) => !is_string($value))
                                    ->thenInvalid(self::WIDGETS_ARRAY_VALUE_ERROR)
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('expanded_left')
                            ->info('The id of the widget that should be expanded on the left side.')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('expanded_right')
                            ->info('The id of the widget that should be expanded on the right side.')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('context_permissions')
                            ->useAttributeAsKey('key')
                            ->arrayPrototype()
                                ->useAttributeAsKey('key')
                                    ->scalarPrototype()
                                        ->validate()
                                            ->ifNotInArray([true, false])
                                            ->thenInvalid(self::PERMISSION_ARRAY_VALUE_ERROR)
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addElementTreeWidgetConfigurationNode(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode(self::TREE_WIDGETS_NODE)
                ->defaultValue([])
                ->useAttributeAsKey('id')
                ->arrayPrototype()
                    ->beforeNormalization()
                        ->always(fn (mixed $v) => is_array($v)
                            ? ConfigKeyMapper::convertKeysForConfig($v)
                            : $v)
                    ->end()
                    ->children()
                        ->scalarNode('name')
                            ->isRequired()
                        ->end()
                        ->scalarNode('element_type')
                            ->defaultValue(ElementTypes::TYPE_OBJECT)
                        ->end()
                        ->scalarNode('page_size')
                            ->defaultNull()
                            ->validate()
                                ->ifTrue(fn ($v) => !is_null($v) && !is_int($v))
                                ->thenInvalid('The "page_size" must be an integer or null.')
                            ->end()
                        ->end()
                        ->arrayNode('icon')
                            ->isRequired()
                            ->beforeNormalization()
                                ->ifNull()
                                ->then(fn () => [])
                            ->end()
                            ->validate()
                                ->ifTrue(fn ($icon) => is_array($icon) &&
                                    (isset($icon['type']) xor isset($icon['value']))
                                )
                                ->thenInvalid('Both "type" and "value" must be defined together in "icon".')
                            ->end()
                            ->children()
                                ->enumNode('type')
                                    ->values(ElementIconTypes::values())
                                ->end()
                                ->scalarNode('value')
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('root_folder')
                            ->defaultValue('/')
                            ->isRequired()
                        ->end()
                        ->booleanNode('show_root')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('classes')
                            ->defaultValue([])
                            ->beforeNormalization()
                                ->ifString()->then(function ($v) {
                                    return [$v];
                                })
                            ->end()
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('pql')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('context_permissions')
                            ->scalarPrototype()
                                ->validate()
                                    ->ifNotInArray([true, false])
                                    ->thenInvalid(self::PERMISSION_ARRAY_VALUE_ERROR)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addTwigSandboxNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('twig');
        $node = $treeBuilder->getRootNode();
        $node->info('Configure the Twig sandbox policy.');
        $node->addDefaultsIfNotSet();

        $childNode = $node->children();
        $sandboxNode = $childNode->arrayNode('sandbox_security_policy');
        $sandboxNode->addDefaultsIfNotSet();

        $sandboxChildNode = $sandboxNode->children();
        $sandboxChildNode->arrayNode('tags')
            ->scalarPrototype()->end()
            ->defaultValue(
                [
                    'if',
                    'for',
                    'set',
                ]
            );

        $sandboxChildNode->arrayNode('filters')
            ->scalarPrototype()->end()
            ->defaultValue(
                [
                    // Twig core filters
                    'abs',
                    'capitalize',
                    'date',
                    'date_modify',
                    'default',
                    'escape',
                    'filter',
                    'find',
                    'first',
                    'format',
                    'join',
                    'json_encode',
                    'keys',
                    'last',
                    'length',
                    'lower',
                    'map',
                    'merge',
                    'nl2br',
                    'number_format',
                    'raw',
                    'reduce',
                    'replace',
                    'reverse',
                    'round',
                    'shuffle',
                    'slice',
                    'sort',
                    'split',
                    'striptags',
                    'title',
                    'trim',
                    'upper',
                    'url_encode',
                    // twig/string-extra (Twig\Extra\String\StringExtension)
                    'plural',
                    'singular',
                    // twig/intl-extra (Twig\Extra\Intl\IntlExtension)
                    'country_name',
                    'currency_name',
                    'currency_symbol',
                    'format_currency',
                    'format_date',
                    'format_datetime',
                    'format_number',
                    'format_time',
                    'language_name',
                    'locale_name',
                ]
            );

        $sandboxChildNode->arrayNode('functions')
            ->scalarPrototype()->end()
            ->defaultValue(
                [
                    'range',
                    'date',
                    'max',
                    'min',
                    'random',
                ]
            );

        return $node;
    }

    private function addMcpNode(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('mcp')
                ->addDefaultsIfNotSet()
                ->info('MCP (Model Context Protocol) server configuration (experimental)')
                ->children()
                    ->arrayNode('authentication')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('tokens')
                                ->info(
                                    'Map of Pimcore username to bearer token list. '
                                    . 'Tokens can reference env vars: \'%%env(MY_TOKEN)%%\'.'
                                )
                                ->useAttributeAsKey('username')
                                ->arrayPrototype()
                                    ->scalarPrototype()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addOAuthNode(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('oauth')
                ->addDefaultsIfNotSet()
                ->info(
                    'Embedded OAuth 2.1 authorization server (experimental; opt-in). '
                    . 'Isolated from the application\'s global security configuration.'
                )
                ->children()
                    ->booleanNode('enabled')
                        ->info('Master switch for the embedded OAuth authorization server. Default off.')
                        ->defaultFalse()
                    ->end()
                    ->scalarNode('issuer')
                        ->info(
                            'Issuer identifier (iss) advertised in metadata and stamped on tokens, '
                            . 'e.g. "https://pimcore.example.com". Null derives it from the request.'
                        )
                        ->defaultNull()
                    ->end()
                    ->integerNode('access_token_ttl')
                        ->info('Access-token lifetime in seconds.')
                        ->defaultValue(3600)
                    ->end()
                    ->integerNode('auth_code_ttl')
                        ->info('Authorization-code lifetime in seconds.')
                        ->defaultValue(600)
                    ->end()
                    ->integerNode('refresh_token_ttl')
                        ->info('Refresh-token lifetime in seconds.')
                        ->defaultValue(2592000)
                    ->end()
                    ->arrayNode('keys')
                        ->addDefaultsIfNotSet()
                        ->info('Signing/encryption key material. Reference via env vars; never commit secrets.')
                        ->children()
                            ->scalarNode('private_key')
                                ->info('Path or contents of the JWT signing private key.')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('public_key')
                                ->info('Path or contents of the JWT signing public key.')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('passphrase')
                                ->info('Passphrase for the private key, if any.')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('encryption_key')
                                ->info('Encryption key for auth codes and refresh tokens.')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('clients')
                        ->info('Pre-registered first-party clients (e.g. Pimcore Agent, Studio MCP).')
                        ->useAttributeAsKey('identifier')
                        ->normalizeKeys(false)
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('name')->isRequired()->end()
                                ->arrayNode('redirect_uris')
                                    ->isRequired()
                                    ->scalarPrototype()->end()
                                ->end()
                                ->booleanNode('confidential')->defaultFalse()->end()
                                ->scalarNode('secret')
                                    ->info('Only for confidential clients; reference via env var.')
                                    ->defaultNull()
                                ->end()
                                ->integerNode('service_user')
                                    ->info('Pimcore user id the client_credentials grant acts as (service account).')
                                    ->defaultNull()
                                ->end()
                                ->arrayNode('scopes')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('resources')
                        ->info('Protected resources (audiences). Each entry is one endpoint bound as a token audience.')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('uri')
                                    ->isRequired()
                                    ->info('Canonical resource URI (audience).')
                                ->end()
                                ->arrayNode('scopes_supported')
                                    ->scalarPrototype()->end()
                                    ->defaultValue(['mcp:read'])
                                ->end()
                                ->arrayNode('authorization_servers')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addDefaultFromEmail(ArrayNodeDefinition $node): void
    {
        $node->children()
                ->scalarNode('studio_from_default_email')
                    ->defaultValue('studio-admin@pimcore.com')
                    ->cannotBeEmpty()
                ->end()
            ->end();
    }

    private function addGdprDataExtractorNode(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('gdpr_data_extractor')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('data_objects')
                        ->addDefaultsIfNotSet()
                        ->info('Settings for DataObjects DataProvider')
                        ->children()
                            ->arrayNode('classes')
                                ->info('Configure which classes should be considered, array key is class name')
                                ->useAttributeAsKey('name')
                                ->defaultValue([])
                                ->arrayPrototype()
                                    ->beforeNormalization()
                                        ->always(fn (mixed $v) => is_array($v)
                                            ? ConfigKeyMapper::convertKeysForConfig($v)
                                            : $v)
                                    ->end()
                                    ->children()
                                        ->booleanNode('allow_delete')
                                            ->info('Allow delete of objects directly in preview grid.')
                                            ->defaultFalse()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('assets')
                        ->addDefaultsIfNotSet()
                        ->info('Settings for Assets DataProvider')
                        ->children()
                            ->arrayNode('types')
                                ->info('Configure which asset types should be considered')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addAdminSettingsNode(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode(self::ADMIN_SETTINGS_NODE)
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('branding')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('background_shade')
                                ->defaultValue('')
                            ->end()
                            ->scalarNode('brand_color')
                                ->defaultValue('')
                            ->end()
                            ->arrayNode('login_screen_custom_background_image')
                                ->children()
                                    ->scalarNode('id')->end()
                                    ->scalarNode('type')->end()
                                ->end()
                            ->end()
                            ->arrayNode('custom_logo')
                                ->children()
                                    ->scalarNode('id')->end()
                                    ->scalarNode('type')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('assets')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('hide_edit_image')
                                ->defaultFalse()
                            ->end()
                            ->booleanNode('disable_tree_preview')
                                ->defaultFalse()
                            ->end()
                        ->end()
            ->end()
            ->end();
    }

    private function addRateLimitingNode(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('rate_limiting')
                ->addDefaultsIfNotSet()
                ->info('General rate limiting configuration for all Studio API endpoints.')
                ->children()
                    ->booleanNode('enabled')
                        ->info('Enable or disable general rate limiting for all Studio API endpoints.')
                        ->defaultTrue()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addTranslation(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('translations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('path')->defaultNull()->end()
                        ->booleanNode('auto_create_missing_keys')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end();
    }
}
