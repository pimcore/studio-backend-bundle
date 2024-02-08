<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\DependencyInjection;

use Exception;
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
    }

    public function prepend(ContainerBuilder $container): void
    {
        $apiPlatformConfig = [
            'mapping'=>[
                'paths'=> [
                    __DIR__ . '/../../config/api_platform/',
                ],
            ],
        ];
        $container->prependExtensionConfig('api_platform', $apiPlatformConfig);
    }
}
