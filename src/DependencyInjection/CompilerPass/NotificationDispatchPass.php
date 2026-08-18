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
 * It also rejects an unusable type id here, where the failure is a build error a contributing
 * bundle sees immediately. That check is best effort by construction: a descriptor can only be
 * read at compile time when it is statically constructible, which is the normal case but not a
 * guarantee. {@see NotificationTypeRegistry} therefore repeats it over the fully-resolved set and
 * remains the authoritative check.
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
     * The id is persisted in `notifications`.`type` (VARCHAR(20)) and in the subscription row, so
     * an over-long or duplicated id is a defect that must never reach a running installation.
     *
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
     * (collected) only when a consumer exists, and dropped otherwise. The in-app 'popup' substrate
     * is always available and is not a tagged channel, so it is unaffected. Installing a bundle that
     * contributes an externally-deliverable type brings the channels back automatically.
     *
     * @param string[] $channelIds
     */
    private function applyTransportChannels(
        ContainerBuilder $container,
        array $channelIds,
        bool $allowsExternalDelivery,
    ): void {
        foreach ($channelIds as $id) {
            if ($allowsExternalDelivery) {
                $this->tag($container->getDefinition($id), ChannelInterface::TAG);
            } else {
                $container->removeDefinition($id);
            }
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
     * Materialises the descriptor so its own answers can be read at compile time, or null when it
     * cannot be built here. Only purely positional, scalar arguments can be resolved at this point;
     * a descriptor wired with service references, parameters or named arguments — or one that fails
     * to construct — cannot be read, and the caller decides what to assume in its absence.
     *
     * A descriptor is a bag of constants and normally takes no arguments at all, so this resolves
     * in practice; the null path exists so an unusual one degrades instead of breaking the build.
     * Note the consequence for contributors: a descriptor's constructor runs during container
     * compilation and must therefore be free of side effects.
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

        // The caller only reaches this for a class that already passed an is_a() check, so the
        // instanceof is a formality — but it narrows newInstanceArgs()'s `object` honestly,
        // which a @var annotation would only have asserted.
        return $descriptor instanceof NotificationTypeDescriptorInterface ? $descriptor : null;
    }
}
