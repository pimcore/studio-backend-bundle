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

use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidHostException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\WidgetTypes;
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

    public const string TREE_WIDGETS_NODE = WidgetTypes::ELEMENT_TREE->value . '_widgets';

    private const string WIDGETS_ARRAY_VALUE_ERROR = 'Each widget id value must be a string.';

    private const string PERMISSION_ARRAY_VALUE_ERROR = 'Each permission value must be a boolean.';

    /**
     * {@inheritdoc}
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
        $this->addNoteTypes($rootNode);
        $this->addDataObjectAdapterMapping($rootNode);
        $this->addUserNode($rootNode);
        $this->addServerNode($rootNode);
        $this->addWidgetTypesNode($rootNode);
        $this->addPerspectivesConfigurationNode($rootNode);
        $this->addElementTreeWidgetConfigurationNode($rootNode);

        ConfigurationHelper::addConfigLocationWithWriteTargetNodes(
            $rootNode,
            [
                self::TREE_WIDGETS_NODE => PIMCORE_CONFIGURATION_DIRECTORY . '/' . self::TREE_WIDGETS_NODE,
                self::PERSPECTIVES_NODE => PIMCORE_CONFIGURATION_DIRECTORY . '/' . self::PERSPECTIVES_NODE,
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
                            ->arrayNode('advanced_column_supported_data_types')
                               ->scalarPrototype()
                                    ->validate()
                                        ->ifTrue(fn ($v) => !is_string($v))
                                        ->thenInvalid(
                                            'advanced_column_supported_data_types must be an array of strings.'
                                        )
                                    ->end()
                               ->end()
                           ->end()
                           ->arrayNode('predefined_columns')
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
                            'The url to the mercure hub for the server. This can also be the docker container name.'.
                            ' (e.g. http://mercure/.well-known/mercure)'
                        )
                        ->defaultValue('http://mercure/.well-known/mercure')
                    ->end()
                    ->scalarNode('hub_url_client')
                        ->info('The url to the mercure hub for the (frontend) client.')
                        ->defaultValue('http://localhost/hub')
                    ->end()
                    ->scalarNode('jwt_key')
                        ->info('The key used to sign the JWT token. Must be longer than 256 bits.')
                        ->isRequired()
                    ->end()
                     ->integerNode('cookie_lifetime')
                        ->info('Lifetime of the mercure cookie in seconds. Default is one hour.')
                        ->defaultValue(3600)
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
                        ->defaultValue(',')
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

    private function addDataObjectAdapterMapping(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('data_object_data_adapter_mapping')
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
                        ->arrayNode('widgetsLeft')
                            ->useAttributeAsKey('id')
                            ->scalarPrototype()
                                ->validate()
                                    ->ifTrue(fn ($value) => !is_string($value))
                                    ->thenInvalid(self::WIDGETS_ARRAY_VALUE_ERROR)
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('widgetsRight')
                            ->useAttributeAsKey('id')
                            ->scalarPrototype()
                                ->validate()
                                    ->ifTrue(fn ($value) => !is_string($value))
                                    ->thenInvalid(self::WIDGETS_ARRAY_VALUE_ERROR)
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('widgetsBottom')
                            ->useAttributeAsKey('id')
                            ->scalarPrototype()
                                ->validate()
                                    ->ifTrue(fn ($value) => !is_string($value))
                                    ->thenInvalid(self::WIDGETS_ARRAY_VALUE_ERROR)
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('expandedLeft')
                            ->info('The id of the widget that should be expanded on the left side.')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('expandedRight')
                            ->info('The id of the widget that should be expanded on the right side.')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('contextPermissions')
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
                    ->children()
                        ->scalarNode('name')
                            ->isRequired()
                        ->end()
                        ->scalarNode('elementType')
                            ->defaultValue(ElementTypes::TYPE_OBJECT)
                        ->end()
                        ->scalarNode('pageSize')
                            ->defaultNull()
                            ->validate()
                                ->ifTrue(fn ($v) => !is_null($v) && !is_int($v))
                                ->thenInvalid('The "pageSize" must be an integer or null.')
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
                        ->scalarNode('rootFolder')
                            ->defaultValue('/')
                            ->isRequired()
                        ->end()
                        ->booleanNode('showRoot')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('classes')
                            ->defaultValue([])
                            ->beforeNormalization()
                                ->ifString()->then(function ($v) { return [$v]; })
                            ->end()
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('pql')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('contextPermissions')
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
}
