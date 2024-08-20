<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Hydrator;

use Carbon\Carbon;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\NotificationListItem;
use Pimcore\Model\Notification;
use Pimcore\Model\User;

/**
 * @internal
 */
final readonly class NotificationHydrator implements NotificationHydratorInterface
{
    public function hydrate(Notification $notification): NotificationListItem
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
