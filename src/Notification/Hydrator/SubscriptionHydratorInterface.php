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

use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\NotificationTypeDescriptorInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\EffectiveSubscription;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\SubscribableType;

/**
 * @internal
 */
interface SubscriptionHydratorInterface
{
    /**
     * @param string[] $availableChannelIds every channel offerable in this installation
     * @param string[] $supportedChannelIds  the subset this type can actually use
     */
    public function hydrate(
        NotificationTypeDescriptorInterface $descriptor,
        EffectiveSubscription $subscription,
        array $availableChannelIds,
        array $supportedChannelIds,
        string $translationKey,
        string $descriptionKey,
    ): SubscribableType;
}
