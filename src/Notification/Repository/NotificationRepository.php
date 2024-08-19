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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Repository;

use Pimcore\Bundle\StaticResolverBundle\Models\Notification\NotificationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Model\Notification;
use Pimcore\Model\Notification\Listing;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class NotificationRepository implements NotificationRepositoryInterface
{
    private const DEFAULT_ORDER_KEY = 'creationDate';

    public function __construct(
        private NotificationResolverInterface $notificationResolver
    ) {

    }

    public function getListingForCurrentUser(
        UserInterface $user,
        CollectionParameters $parameters
    ): Listing {
        $listing = $this->getListing($parameters);
        $listing->addConditionParam(
            'recipient = :recipientId',
            ['recipientId' => $user->getId()]
        );

        return $listing;

    }

    public function getNotificationById(int $id): Notification
    {
        $notification = $this->notificationResolver->getById($id);
        if ($notification === null) {
            throw new NotFoundException('notification', $id);
        }

        return $notification;
    }

    public function getListing(
        CollectionParameters $parameters
    ): Listing {
        $limit = $parameters->getPageSize();
        $listing = new Listing();
        $listing->setLimit($limit);
        $listing->setOffset(($parameters->getPage() - 1) * $limit);
        $listing->setOrderKey(self::DEFAULT_ORDER_KEY);
        $listing->setOrder('DESC');

        return $listing;
    }
}
