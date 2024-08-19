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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Notification\Event\NotificationEvent;
use Pimcore\Bundle\StudioBackendBundle\Notification\Event\NotificationListEvent;
use Pimcore\Bundle\StudioBackendBundle\Notification\Hydrator\NotificationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Repository\NotificationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Notification;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Notification as NotificationModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private NotificationHydratorInterface $notificationHydrator,
        private NotificationRepositoryInterface $notificationRepository,
        private SecurityServiceInterface $securityService
    ) {
    }

    /**
     * @throws AccessDeniedException
     * @throws UserNotFoundException
     */
    public function getNotificationById(int $id): Notification
    {
        $notification = $this->notificationRepository->getNotificationById($id);
        if ($this->securityService->getCurrentUser() !== $notification->getRecipient()) {
            throw new AccessDeniedException('User has no permissions to access this notification');
        }
        if (!$notification->isRead()) {
            $this->markAsRead($notification);
        }

        $entry = $this->notificationHydrator->hydrateDetail($notification);
        $this->eventDispatcher->dispatch(
            new NotificationEvent($entry),
            NotificationEvent::EVENT_NAME
        );

        return $entry;
    }

    /**
     * @throws AccessDeniedException
     * @throws UserNotFoundException
     */
    public function markNotificationAsRead(int $id): void
    {
        $notification = $this->notificationRepository->getNotificationById($id);
        if ($this->securityService->getCurrentUser()->getId() !== $notification->getRecipient()) {
            throw new AccessDeniedException('User has no permissions to access this notification');
        }
        $this->markAsRead($notification);
    }

    /**
     * @throws UserNotFoundException
     */
    public function listNotifications(CollectionParameters $parameters): Collection
    {
        $list = [];
        $listing = $this->notificationRepository->getListingForCurrentUser(
            $this->securityService->getCurrentUser(),
            $parameters
        );
        foreach ($listing as $listEntry) {
            $entry = $this->notificationHydrator->hydrate($listEntry);
            $this->eventDispatcher->dispatch(
                new NotificationListEvent($entry),
                NotificationListEvent::EVENT_NAME
            );

            $list[] = $entry;
        }

        return new Collection(
            $listing->count(),
            $list
        );
    }

    /**
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function deleteNotificationById(int $id): void
    {
        $notification = $this->notificationRepository->getNotificationById($id);
        $this->validateNotificationAccess($notification);

        $notification->delete();
    }

    /**
     * @throws AccessDeniedException
     * @throws UserNotFoundException
     */
    private function validateNotificationAccess(Notification $notification): void
    {
        if ($this->securityService->getCurrentUser() !== $notification->getRecipient()) {
            throw new AccessDeniedException('User has no permissions to access this notification');
        }
    }

    private function markAsRead(NotificationModel $notification): void
    {
        $notification->setRead(true);
        $notification->save();
    }
}
