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

namespace Pimcore\Bundle\StudioBackendBundle\DependencyInjection\CompilerPass;

use function in_array;
use Pimcore\Bundle\StudioBackendBundle\Exception\MustImplementInterfaceException;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Provider\ClientTopicProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Provider\ServerTopicProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader\TaggedIteratorAdapter;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\MustImplementInterfaceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
