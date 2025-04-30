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
use Pimcore\Bundle\StudioBackendBundle\Mercure\Provider\ClientTopicProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Provider\ServerTopicProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader\TaggedIteratorAdapter;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\MustImplementInterfaceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final class MercureTopicsProviderPass implements CompilerPassInterface
{
    use MustImplementInterfaceTrait;

    /**
     * @throws MustImplementInterfaceException
     */
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = array_keys(
            [
                ... $container->findTaggedServiceIds(TaggedIteratorAdapter::TOPIC_LOADER_TAG),
            ]
        );

        foreach ($taggedServices as $class) {
            $classInterfaces = class_implements($class, false);
            if (
                $classInterfaces === false ||
                (
                    !in_array(ServerTopicProviderInterface::class, $classInterfaces, true) &&
                    !in_array(ClientTopicProviderInterface::class, $classInterfaces, true)
                )

            ) {
                throw new MustImplementInterfaceException(
                    sprintf(
                        '%s must implement either %s or %s',
                        $class,
                        ServerTopicProviderInterface::class,
                        ClientTopicProviderInterface::class
                    )
                );
            }
        }
    }
}
