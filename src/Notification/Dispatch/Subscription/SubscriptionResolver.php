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
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\NotificationType;
use function array_unique;
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

    public function resolve(int $userId, NotificationType $type): EffectiveSubscription
    {
        return $this->merge(
            $type,
            $this->repository->getByUserAndType($userId, $type->getTypeId())
        );
    }

    public function resolveAll(int $userId): array
    {
        $stored = $this->repository->getByUser($userId);

        $effective = [];
        foreach ($this->typeRegistry->getTypes() as $type) {
            $typeId = $type->getTypeId();
            $effective[$typeId] = $this->merge($type, $stored[$typeId] ?? null);
        }

        return $effective;
    }

    /**
     * A stored row wins over the type default; the result is narrowed to what is actually
     * offerable.
     */
    private function merge(
        NotificationType $type,
        ?NotificationSubscription $stored,
    ): EffectiveSubscription {
        $subscribed = $type->isSubscriptionLocked()
            ? true
            : ($stored?->isSubscribed() ?? $type->isSubscribedByDefault());

        // null = never chosen (defaults apply); [] = deliberate "none" and must survive
        $chosen = $stored?->getChannels() ?? $type->getDefaultChannels();

        if (!$subscribed) {
            return new EffectiveSubscription($type->getTypeId(), false, []);
        }

        $supported = $this->channelRegistry->getSupportedChannelIds($type);

        // dedupe: a type with duplicate default channels would otherwise send the same email twice
        return new EffectiveSubscription(
            $type->getTypeId(),
            true,
            array_values(
                array_unique(
                    array_filter(
                        $chosen,
                        static fn (string $channel): bool => in_array($channel, $supported, true)
                    )
                )
            )
        );
    }
}
