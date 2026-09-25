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
            (new Carbon($notification->getCreationDate(), 'UTC'))->getTimeStamp(),
            $this->getRecipientName($notification->getSender()),
        );
    }

    public function hydrateMinimal(NotificationModel $notification, bool $popup = true): NotificationMinimal
    {
        return new NotificationMinimal(
            $notification->getId(),
            $notification->getType() ?? 'info',
            $notification->getTitle(),
            $notification->isRead(),
            (new Carbon($notification->getCreationDate(), 'UTC'))->getTimeStamp(),
            $notification->getRecipient()?->getId() ?? 0,
            $this->getRecipientName($notification->getSender()),
            $popup,
            $notification->getPayload(),
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
            (new Carbon($notification->getCreationDate(), 'UTC'))->getTimeStamp(),
            $this->getRecipientName($notification->getSender()),
            $notification->getMessage(),
            $notification->getPayload(),
            $notification->getLinkedElementType(),
            $notification->getLinkedElement()?->getId(),
            $notification->getLinkedElement()?->getRealFullPath()
        );
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
