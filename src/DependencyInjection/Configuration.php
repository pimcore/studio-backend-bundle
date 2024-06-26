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

use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidHostException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\ResizeModes;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'pimcore_studio_backend';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);

        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();
        $this->addOpenApiScanPathsNode($rootNode);
        $this->addApiTokenNode($rootNode);
        $this->addAllowedHostsForCorsNode($rootNode);
        $this->addSecurityFirewall($rootNode);
        $this->addDefaultAssetFormats($rootNode);
        $this->addRecycleBinThreshold($rootNode);
        $this->addMercureConfiguration($rootNode);

        return $treeBuilder;
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
                                ->values(MimeTypes::ALLOWED_FORMATS)
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

    private function addMercureConfiguration(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('mercure_settings')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('hub_url_server')
                        ->defaultValue('http://localhost:3000/.well-known/mercure')
                    ->end()
                    ->scalarNode('hub_url_client')
                        ->defaultValue('http://localhost:3000/.well-known/mercure')
                    ->end()
                    ->scalarNode('jwt_key')
                        ->info('The key used to sign the JWT token')
                        ->defaultNull()
                    ->end()
                ->end()
            ->end();
    }
}
