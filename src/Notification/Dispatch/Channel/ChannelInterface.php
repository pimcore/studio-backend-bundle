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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel;

use Pimcore\Model\Notification;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A transport that delivers a notification outside the application — email, chat, whatever a
 * bundle needs. Any bundle may contribute one; tagged services are collected automatically and
 * a matching column appears in the preferences screen with no frontend change.
 *
 * {@see EmailChannel} is the implementation shipped here. A channel is only reachable by types
 * that return true from allowsExternalDelivery(), and the only type this bundle registers — the
 * general catch-all — deliberately does not: a bucket of unclassified notifications is not
 * something to email. So on a core-only install every registered channel is untagged again by
 * NotificationDispatchPass, and installing a bundle with an externally-deliverable type brings
 * them back.
 *
 * The in-app pop-up is NOT a transport and has no implementation here — it is a preference read
 * when the notification is published over Mercure.
 */
#[AutoconfigureTag(ChannelInterface::TAG)]
interface ChannelInterface
{
    public const string TAG = 'pimcore.studio_backend.notification_channel';

    /**
     * Stable channel id, e.g. `email`. Stored in the user's subscription row and used as the
     * column key in the preferences screen.
     */
    public function getName(): string;

    /**
     * Column order in the preferences screen.
     */
    public function getSortOrder(): int;

    /**
     * IMPORTANT: this runs inside the request that produced the notification. It MUST NOT
     * block on the network — dispatch a Messenger message and return, so the transport's own
     * latency and retry policy stay its business rather than the producer's.
     *
     * Throwing is safe: the dispatcher logs and continues, so a failing channel can never
     * break the action that produced the notification, nor prevent other channels from
     * delivering.
     */
    public function send(Notification $notification, UserInterface $recipient): void;
}
