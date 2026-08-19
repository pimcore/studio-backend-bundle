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
 * A transport that delivers a notification outside the application. Any bundle may contribute
 * one; tagged services are collected automatically. The in-app pop-up is not a transport — it
 * is a preference read when the notification is published over Mercure.
 */
#[AutoconfigureTag(ChannelInterface::TAG)]
interface ChannelInterface
{
    public const string TAG = 'pimcore.studio_backend.notification_channel';

    /**
     * Stable channel id, e.g. `email` — stored in the user's subscription row.
     */
    public function getName(): string;

    /**
     * Column order in the preferences screen.
     */
    public function getSortOrder(): int;

    /**
     * Why this channel cannot reach the given user right now (e.g. no email address on the
     * account), as a translation key — or null when it can.
     */
    public function unavailableReasonFor(UserInterface $recipient): ?string;

    /**
     * Runs inside the request that produced the notification, so it must not block on the
     * network — dispatch a Messenger message and return. Throwing is safe: the dispatcher
     * logs and continues.
     */
    public function send(Notification $notification, UserInterface $recipient): void;
}
