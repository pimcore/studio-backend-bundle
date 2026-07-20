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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription;

use Pimcore\Bundle\StudioBackendBundle\Entity\Notification\NotificationSubscription;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\NotificationTypeDescriptorInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistryInterface;
use function array_values;
use function in_array;

/**
 * @internal
 */
final readonly class SubscriptionResolver implements SubscriptionResolverInterface
{
    public function __construct(
        private SubscriptionRepositoryInterface $repository,
        private NotificationTypeRegistryInterface $typeRegistry,
        private ChannelRegistryInterface $channelRegistry,
    ) {
    }

    public function resolve(int $userId, NotificationTypeDescriptorInterface $descriptor): EffectiveSubscription
    {
        return $this->merge(
            $descriptor,
            $this->repository->getByUserAndType($userId, $descriptor->getTypeId())
        );
    }

    public function resolveAll(int $userId): array
    {
        $stored = $this->repository->getByUser($userId);

        $effective = [];
        foreach ($this->typeRegistry->getDescriptors() as $descriptor) {
            $typeId = $descriptor->getTypeId();
            $effective[$typeId] = $this->merge($descriptor, $stored[$typeId] ?? null);
        }

        return $effective;
    }

    /**
     * Merge order: a stored row wins over the descriptor default, then the result is narrowed
     * to what is actually offerable.
     */
    private function merge(
        NotificationTypeDescriptorInterface $descriptor,
        ?NotificationSubscription $stored,
    ): EffectiveSubscription {
        $subscribed = $descriptor->isSubscriptionLocked()
            ? true
            : ($stored?->isSubscribed() ?? $descriptor->isSubscribedByDefault());

        // Null means never chosen, so fall back to defaults. An empty array is a deliberate
        // "none" and must survive.
        $chosen = $stored?->getChannels() ?? $descriptor->getDefaultChannels();

        if (!$subscribed) {
            return new EffectiveSubscription($descriptor->getTypeId(), false, []);
        }

        $supported = $this->channelRegistry->getSupportedChannelIds($descriptor);

        return new EffectiveSubscription(
            $descriptor->getTypeId(),
            true,
            array_values(
                array_filter(
                    $chosen,
                    static fn (string $channel): bool => in_array($channel, $supported, true)
                )
            )
        );
    }
}
