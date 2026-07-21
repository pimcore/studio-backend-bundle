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

use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\ChannelInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\NotificationTypeDescriptorInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use function class_exists;
use function is_a;

/**
 * Tags every notification type descriptor and delivery channel so the registries collect them.
 *
 * This is done with a compiler pass rather than #[AutoconfigureTag] on the interfaces because the
 * interface attribute does not tag implementers in Pimcore's container. A pass runs after every
 * bundle's extension has loaded, so a descriptor or channel contributed by any bundle — the only
 * way the framework is extensible — is picked up without that bundle needing to know the tag name.
 *
 * @internal
 */
final readonly class NotificationDispatchPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $definition) {
            if ($definition->isAbstract() || $definition->isSynthetic()) {
                continue;
            }

            $class = $definition->getClass();
            if ($class === null || !class_exists($class)) {
                continue;
            }

            if ((new ReflectionClass($class))->isAbstract()) {
                continue;
            }

            $this->tagIfImplements(
                $definition,
                $class,
                NotificationTypeDescriptorInterface::class,
                NotificationTypeDescriptorInterface::TAG
            );
            $this->tagIfImplements(
                $definition,
                $class,
                ChannelInterface::class,
                ChannelInterface::TAG
            );
        }
    }

    private function tagIfImplements(
        Definition $definition,
        string $class,
        string $interface,
        string $tag,
    ): void {
        // hasTag keeps it idempotent, so a bundle that already tags its service explicitly is
        // not tagged twice.
        if (is_a($class, $interface, true) && !$definition->hasTag($tag)) {
            $definition->addTag($tag);
        }
    }
}
