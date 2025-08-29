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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Notification\Event\NotificationEvent;
use Pimcore\Bundle\StudioBackendBundle\Notification\Event\NotificationListEvent;
use Pimcore\Bundle\StudioBackendBundle\Notification\Hydrator\NotificationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Repository\NotificationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Notification;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\NotificationListItem;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\UnreadCount;
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
     * @throws ForbiddenException
     * @throws UserNotFoundException
     */
    public function getNotificationById(int $id): Notification
    {
        $notification = $this->notificationRepository->getNotificationById($id);
        $this->validateNotificationAccess($notification);
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
     * @throws ForbiddenException
     * @throws UserNotFoundException
     */
    public function markNotificationAsRead(int $id): void
    {
        $notification = $this->notificationRepository->getNotificationById($id);
        $this->validateNotificationAccess($notification);

        $this->markAsRead($notification);
    }

    /**
     * @throws UserNotFoundException
     */
    public function listNotifications(FilterParameter $parameters): Collection
    {
        $list = [];
        $listing = $this->notificationRepository->getListingForCurrentUser(
            $this->securityService->getCurrentUser(),
            $parameters
        );
        foreach ($listing as $listEntry) {
            $list[] = $this->hydrateListItem($listEntry);
        }

        return new Collection(
            $listing->count(),
            $list
        );
    }

    /**
     * @throws ForbiddenException
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
     * @throws UserNotFoundException
     */
    public function deleteAllUserNotifications(): void
    {
        $notifications = $this->notificationRepository->getListingForCurrentUser(
            $this->securityService->getCurrentUser()
        );

        foreach ($notifications as $notification) {
            $notification->delete();
        }
    }

    /**
     * @throws UserNotFoundException
     */
    public function getUnreadNotificationsCount(): UnreadCount
    {
        $count = $this->notificationRepository->getUnreadCountByUser(
            $this->securityService->getCurrentUser()
        );

        return new UnreadCount($count);
    }

    public function hydrateListItem(NotificationModel $notification): NotificationListItem
    {
        $entry = $this->notificationHydrator->hydrate($notification);
        $this->eventDispatcher->dispatch(
            new NotificationListEvent($entry),
            NotificationListEvent::EVENT_NAME
        );

        return $entry;
    }

    /**
     * @throws ForbiddenException
     * @throws UserNotFoundException
     */
    private function validateNotificationAccess(NotificationModel $notification): void
    {
        if ($this->securityService->getCurrentUser() !== $notification->getRecipient()) {
            throw new ForbiddenException('User has no permissions to access this notification');
        }
    }

    private function markAsRead(NotificationModel $notification): void
    {
        $notification->setRead(true);
        $notification->save();
    }
}
