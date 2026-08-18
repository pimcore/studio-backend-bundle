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

use Carbon\Carbon;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Notification;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\NotificationListItem;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\NotificationMinimal;
use Pimcore\Model\Notification as NotificationModel;
use Pimcore\Model\User;
use function date_default_timezone_get;

/**
 * @internal
 */
final readonly class NotificationHydrator implements NotificationHydratorInterface
{
    public function hydrate(NotificationModel $notification): NotificationListItem
    {
        return new NotificationListItem(
            $notification->getId(),
            $notification->getType(),
            $notification->getTitle(),
            $notification->isRead(),
            (bool)$notification->getLinkedElementType(),
            $this->getCreationTimestamp($notification),
            $this->getRecipientName($notification->getSender()),
        );
    }

    public function hydrateMinimal(NotificationModel $notification): NotificationMinimal
    {
        return new NotificationMinimal(
            $notification->getId(),
            $notification->getType() ?? 'info',
            $notification->getTitle(),
            $notification->isRead(),
            $this->getCreationTimestamp($notification),
            $notification->getRecipient()?->getId() ?? 0,
            $this->getRecipientName($notification->getSender()),
        );
    }

    public function hydrateDetail(NotificationModel $notification): Notification
    {
        return new Notification(
            $notification->getId(),
            $notification->getType(),
            $notification->getTitle(),
            $notification->isRead(),
            (bool)$notification->getLinkedElementType(),
            $this->getCreationTimestamp($notification),
            $this->getRecipientName($notification->getSender()),
            $notification->getMessage(),
            $notification->getPayload(),
            $notification->getLinkedElementType(),
            $notification->getLinkedElement()?->getId(),
            $notification->getLinkedElement()?->getRealFullPath()
        );
    }

    /**
     * Notification\Dao::save() stores creationDate as a naive wall-clock string in the
     * application's default timezone (general.timezone), so it has to be parsed in that
     * same timezone instead of UTC to end up with the correct epoch.
     */
    private function getCreationTimestamp(NotificationModel $notification): int
    {
        return (new Carbon($notification->getCreationDate(), date_default_timezone_get()))->getTimestamp();
    }

    private function getRecipientName(?User $recipient): ?string
    {
        if ($recipient === null) {
            return null;
        }

        $name = $recipient->getFullName();
        if ($name === '') {
            return $recipient->getUsername();
        }

        return $name;
    }
}
