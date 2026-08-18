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

use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidNotificationTypeException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\ChannelInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\NotificationTypeDescriptorInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistryInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Throwable;
use function class_exists;
use function is_a;
use function is_int;
use function is_scalar;
use function sprintf;
use function strlen;

/**
 * Tags every notification type descriptor so the registry collects them, and registers the delivery
 * channels only when at least one type can actually be delivered externally.
 *
 * This is done with a compiler pass rather than #[AutoconfigureTag] on the interfaces because the
 * interface attribute does not tag implementers in Pimcore's container. A pass runs after every
 * bundle's extension has loaded, so a descriptor or channel contributed by any bundle — the only
 * way the framework is extensible — is picked up without that bundle needing to know the tag name.
 *
 * Type ids are also validated here so a bad one fails the build. Best effort: only a statically
 * constructible descriptor can be read at compile time, so NotificationTypeRegistry repeats the
 * checks and stays authoritative.
 *
 * @internal
 */
final readonly class NotificationDispatchPass implements CompilerPassInterface
{
    /**
     * @throws InvalidNotificationTypeException
     */
    public function process(ContainerBuilder $container): void
    {
        $channelIds = [];
        $allowsExternalDelivery = false;
        $seenTypeIds = [];

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $this->concreteClass($definition);
            if ($class === null) {
                continue;
            }

            if (is_a($class, NotificationTypeDescriptorInterface::class, true)) {
                $this->tag($definition, NotificationTypeDescriptorInterface::TAG);

                $descriptor = $this->materialise($definition, $class);

                // An opaque descriptor is assumed external-capable, so a transport channel a
                // bundle actually wants is never stripped on a false negative.
                $allowsExternalDelivery = $allowsExternalDelivery
                    || $descriptor === null
                    || $descriptor->allowsExternalDelivery();

                if ($descriptor !== null) {
                    $this->validateTypeId($descriptor->getTypeId(), $id, $seenTypeIds);
                }
            }

            if (is_a($class, ChannelInterface::class, true)) {
                $channelIds[] = $id;
            }
        }

        $this->applyTransportChannels($container, $channelIds, $allowsExternalDelivery);
    }

    /**
     * @param array<string, string> $seenTypeIds type id => the service id that first claimed it
     *
     * @throws InvalidNotificationTypeException
     */
    private function validateTypeId(string $typeId, string $serviceId, array &$seenTypeIds): void
    {
        if (strlen($typeId) > NotificationTypeRegistryInterface::MAX_TYPE_ID_LENGTH) {
            throw new InvalidNotificationTypeException(
                sprintf(
                    'Notification type id "%s" (service "%s") is %d characters; the ' .
                    'notifications.type column allows at most %d. Choose a shorter id — it is ' .
                    'persisted and cannot be renamed later without breaking stored notifications.',
                    $typeId,
                    $serviceId,
                    strlen($typeId),
                    NotificationTypeRegistryInterface::MAX_TYPE_ID_LENGTH
                )
            );
        }

        if (isset($seenTypeIds[$typeId])) {
            throw new InvalidNotificationTypeException(
                sprintf(
                    'Notification type id "%s" is registered more than once: by service "%s" and ' .
                    'by service "%s".',
                    $typeId,
                    $seenTypeIds[$typeId],
                    $serviceId
                )
            );
        }

        $seenTypeIds[$typeId] = $serviceId;
    }

    /**
     * The concrete, autoloadable class a definition instantiates, or null when the definition is not
     * a real service to classify (abstract, synthetic, classless or an abstract class).
     */
    private function concreteClass(Definition $definition): ?string
    {
        if ($definition->isAbstract() || $definition->isSynthetic()) {
            return null;
        }

        $class = $definition->getClass();
        if ($class === null || !class_exists($class)) {
            return null;
        }

        return (new ReflectionClass($class))->isAbstract() ? null : $class;
    }

    /**
     * A transport channel (email today) only makes sense when at least one notification type can be
     * delivered externally. With just the built-in 'info' catch-all — which never allows it — a
     * core-only install would otherwise carry a dead channel: an extra column on the preferences
     * screen and an instantiated mailer no notification could ever reach. So the channels are tagged
     * (collected) only when a consumer exists, and untagged otherwise. The in-app 'popup' substrate
     * is always available and is not a tagged channel, so it is unaffected. Installing a bundle that
     * contributes an externally-deliverable type brings the channels back automatically.
     *
     * The gate clears the tag rather than removing the definition: removing it would break any
     * bundle that aliases or injects its own channel.
     *
     * @param string[] $channelIds
     */
    private function applyTransportChannels(
        ContainerBuilder $container,
        array $channelIds,
        bool $allowsExternalDelivery,
    ): void {
        foreach ($channelIds as $id) {
            $definition = $container->getDefinition($id);

            if ($allowsExternalDelivery) {
                $this->tag($definition, ChannelInterface::TAG);

                continue;
            }

            // Only strips this tag, so a channel a bundle tagged itself is gated the same way.
            $definition->clearTag(ChannelInterface::TAG);
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
     * The descriptor instance, or null when it cannot be built at compile time — only positional
     * scalar arguments resolve here. Its constructor runs during container compilation, so it
     * must be free of side effects.
     */
    private function materialise(Definition $definition, string $class): ?NotificationTypeDescriptorInterface
    {
        foreach ($definition->getArguments() as $key => $value) {
            if (!is_int($key) || (!is_scalar($value) && $value !== null && !is_array($value))) {
                return null;
            }
        }

        try {
            $descriptor = (new ReflectionClass($class))->newInstanceArgs($definition->getArguments());
        } catch (Throwable) {
            return null;
        }

        // Narrows newInstanceArgs()'s `object`; the caller has already checked is_a().
        return $descriptor instanceof NotificationTypeDescriptorInterface ? $descriptor : null;
    }
}
