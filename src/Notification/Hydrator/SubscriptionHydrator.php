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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\EffectiveSubscription;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\NotificationType;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\SubscribableType;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\SubscriptionChannel;
use function in_array;

/**
 * @internal
 */
final readonly class SubscriptionHydrator implements SubscriptionHydratorInterface
{
    public function hydrate(
        NotificationType $type,
        EffectiveSubscription $subscription,
        array $availableChannelIds,
        array $supportedChannelIds,
        string $translationKey,
        string $descriptionKey,
    ): SubscribableType {
        $channels = [];
        foreach ($availableChannelIds as $channelId) {
            $channels[] = new SubscriptionChannel(
                $channelId,
                $subscription->hasChannel($channelId),
                in_array($channelId, $supportedChannelIds, true)
            );
        }

        return new SubscribableType(
            $type->getTypeId(),
            $translationKey,
            $descriptionKey,
            $type->getGroup(),
            $type->getSortOrder(),
            $subscription->isSubscribed(),
            $type->isSubscriptionLocked(),
            $channels
        );
    }
}
