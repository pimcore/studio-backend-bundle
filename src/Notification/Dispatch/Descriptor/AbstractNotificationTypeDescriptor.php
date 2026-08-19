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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor;

use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistryInterface;

/**
 * Defaults so a contributing bundle only states what is specific to its type: subscribed and
 * popping up by default, no external delivery.
 */
abstract class AbstractNotificationTypeDescriptor implements NotificationTypeDescriptorInterface
{
    public function getSortOrder(): int
    {
        return 100;
    }

    public function allowsExternalDelivery(): bool
    {
        return false;
    }

    public function getDefaultChannels(): array
    {
        return [ChannelRegistryInterface::POPUP_CHANNEL];
    }

    public function isSubscribedByDefault(): bool
    {
        return true;
    }

    public function isSubscriptionLocked(): bool
    {
        return false;
    }
}
