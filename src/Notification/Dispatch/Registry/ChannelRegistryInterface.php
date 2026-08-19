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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry;

use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\ChannelInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\NotificationTypeDescriptorInterface;

/**
 * @internal
 */
interface ChannelRegistryInterface
{
    /**
     * The in-app pop-up: a channel from the user's point of view, but not a transport — it is
     * read when the notification is published over Mercure rather than sent anywhere.
     */
    public const string POPUP_CHANNEL = 'popup';

    public function getEnabledChannel(string $name): ?ChannelInterface;

    /**
     * Every channel id offerable in this installation: the pop-up plus the enabled transports.
     *
     * @return string[]
     */
    public function getAvailableChannelIds(): array;

    /**
     * Channel ids a specific type can offer: the pop-up, plus every enabled transport if the
     * type allows external delivery. Derived, never enumerated by the descriptor.
     *
     * @return string[]
     */
    public function getSupportedChannelIds(NotificationTypeDescriptorInterface $descriptor): array;
}
