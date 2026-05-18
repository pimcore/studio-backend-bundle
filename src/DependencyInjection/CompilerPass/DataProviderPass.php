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

namespace Pimcore\Bundle\StudioBackendBundle\DependencyInjection\CompilerPass;

use Pimcore\Bundle\StudioBackendBundle\Exception\MustImplementInterfaceException;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\DataProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Service\Loader\TaggedIteratorDataProviderLoader;
use Pimcore\Bundle\StudioBackendBundle\Util\Config\ConfigKeyMapper;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\MustImplementInterfaceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final readonly class DataProviderPass implements CompilerPassInterface
{
    use MustImplementInterfaceTrait;

    private const string GDPR_CONFIG_PARAMETER = 'pimcore_studio_backend.gdpr_data_extractor';

    /**
     * @throws MustImplementInterfaceException
     */
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = array_keys(
            [
                ... $container->findTaggedServiceIds(TaggedIteratorDataProviderLoader::DATA_PROVIDER_TAG),

            ]
        );

        $gdprConfig = $container->getParameter(self::GDPR_CONFIG_PARAMETER);
        $gdprConfig = ConfigKeyMapper::convertKeysForApp($gdprConfig);

        foreach ($taggedServices as $dataProviderId) {
            $this->checkInterface($dataProviderId, DataProviderInterface::class);

            $definition = $container->getDefinition($dataProviderId);
            $definition->setArgument('$gdprConfig', $gdprConfig);
        }
    }
}
