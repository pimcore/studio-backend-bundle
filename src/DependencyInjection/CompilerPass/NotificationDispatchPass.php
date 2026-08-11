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
use Throwable;
use function class_exists;
use function is_a;
use function is_int;
use function is_scalar;

/**
 * Tags every notification type descriptor so the registry collects them, and registers the delivery
 * channels only when at least one type can actually be delivered externally.
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
        $channelIds = [];
        $allowsExternalDelivery = false;

        foreach ($container->getDefinitions() as $id => $definition) {
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

            if (is_a($class, NotificationTypeDescriptorInterface::class, true)) {
                $this->tag($definition, NotificationTypeDescriptorInterface::TAG);
                $allowsExternalDelivery = $allowsExternalDelivery
                    || $this->descriptorAllowsExternalDelivery($definition, $class);
            }

            if (is_a($class, ChannelInterface::class, true)) {
                $channelIds[] = $id;
            }
        }

        // A transport channel (email today) only makes sense when at least one notification type can
        // be delivered externally. With just the built-in 'info' catch-all — which never allows it —
        // a core-only install would otherwise carry a dead channel: an extra column on the
        // preferences screen and an instantiated mailer no notification could ever reach. So the
        // channels are registered only when a consumer exists; the in-app 'popup' substrate is
        // always available and is not a tagged channel, so it is unaffected. Installing a bundle
        // that contributes an externally-deliverable type brings the channels back automatically.
        if (!$allowsExternalDelivery) {
            foreach ($channelIds as $id) {
                $container->removeDefinition($id);
            }

            return;
        }

        foreach ($channelIds as $id) {
            $this->tag($container->getDefinition($id), ChannelInterface::TAG);
        }
    }

    /**
     * hasTag keeps it idempotent, so a bundle that already tags its service explicitly is not
     * tagged twice.
     */
    private function tag(Definition $definition, string $tag): void
    {
        if (!$definition->hasTag($tag)) {
            $definition->addTag($tag);
        }
    }

    /**
     * Materialises the descriptor to read its runtime answer. Only purely positional, scalar
     * arguments can be built at compile time; a descriptor wired with service references,
     * parameters or named arguments — or one that fails to construct — is assumed external-capable,
     * so a transport channel a bundle actually wants is never stripped on a false negative.
     */
    private function descriptorAllowsExternalDelivery(Definition $definition, string $class): bool
    {
        foreach ($definition->getArguments() as $key => $value) {
            if (!is_int($key) || (!is_scalar($value) && $value !== null && !is_array($value))) {
                return true;
            }
        }

        try {
            /** @var NotificationTypeDescriptorInterface $descriptor */
            $descriptor = (new ReflectionClass($class))->newInstanceArgs($definition->getArguments());
        } catch (Throwable) {
            return true;
        }

        return $descriptor->allowsExternalDelivery();
    }
}
