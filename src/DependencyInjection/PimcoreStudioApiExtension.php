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

namespace Pimcore\Bundle\StudioApiBundle\DependencyInjection;

use Exception;
use Pimcore\Bundle\StudioApiBundle\Service\TokenServiceInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */

/**
 * @internal
 */
class PimcoreStudioApiExtension extends Extension implements PrependExtensionInterface
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
        $loader->load('services.yaml');

        // Set default serializer mapping if not provided in the app's config
        if (!isset($config['serializer']['mapping']['paths'])) {
            $config['serializer']['mapping']['paths'] = [__DIR__ . '/../../config/serialization'];
        }

        // Pass the configuration to the custom normalizer
        $container->setParameter(
            'pimcore_studio_api.serializer.mapping.paths',
            $config['serializer']['mapping']['paths']
        );

        $definition = $container->getDefinition(TokenServiceInterface::class);
        $definition->setArgument('$tokenLifetime', $config['api_token']['lifetime']);
    }

    /**
     * @throws Exception
     */
    public function prepend(ContainerBuilder $container): void
    {
        $paths = $this->getPaths();

        if(empty($paths)) {
            return;
        }

        $container->prependExtensionConfig('nelmio_api_doc', [
            'documentation' => [
                'paths' => $paths,
            ],
        ]);

        $thirdPartyBundlesViewFileLocator = (new FileLocator(__DIR__ . '/../../templates/bundles'));
        $container->loadFromExtension('twig', [
            'paths' => [
                $thirdPartyBundlesViewFileLocator->locate('NelmioApiDocBundle') => 'NelmioApiDoc',
            ],
        ]);
    }

    private function getPaths(): array
    {
        $finder = new Finder();
        $finder->files()->in(__DIR__ . '/../../config/api')->name('*.yaml');
        $paths = [];
        foreach($finder as $file) {
            $paths = [...$paths, ...Yaml::parseFile($file->getRealPath())];
        }

        return $paths;
    }
}
