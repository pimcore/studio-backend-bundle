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
     * The in-app pop-up. A channel from the user's point of view — it has a column in the
     * preferences screen and lives in the same stored set — but not a transport: it is read
     * when the notification is published over Mercure rather than sent anywhere.
     */
    public const string POPUP_CHANNEL = 'popup';

    /**
     * Registered transport channels the administrator has enabled, ordered by sort order.
     *
     * @return ChannelInterface[]
     */
    public function getEnabledChannels(): array;

    public function getEnabledChannel(string $name): ?ChannelInterface;

    /**
     * Every channel id offerable anywhere in this installation: the pop-up, plus the enabled
     * transports. Drives the preferences screen's column set — when no transport is enabled
     * the screen renders no channel column at all rather than a column of dead switches.
     *
     * @return string[]
     */
    public function getAvailableChannelIds(): array;

    /**
     * Channel ids a specific type can offer: always the pop-up, plus every enabled transport
     * if the type allows external delivery. Note the capability is derived, never enumerated
     * by the descriptor — that is what lets a newly installed channel light up for existing
     * types without touching the bundles that own them.
     *
     * @return string[]
     */
    public function getSupportedChannelIds(NotificationTypeDescriptorInterface $descriptor): array;
}
