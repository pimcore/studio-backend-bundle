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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Topics;
use Pimcore\Bundle\StudioBackendBundle\Notification\Hydrator\NotificationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Mercure\Events;
use Pimcore\Bundle\StudioBackendBundle\Notification\Repository\NotificationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StudioBackendPathTrait;
use Pimcore\Event\Model\NotificationEvent;
use Pimcore\Event\NotificationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final readonly class NotificationSavedSubscriber implements EventSubscriberInterface
{
    use StudioBackendPathTrait;

    public function __construct(
        private NotificationHydratorInterface $notificationHydrator,
        private NotificationRepositoryInterface $notificationRepository,
        private PublishServiceInterface $publishService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NotificationEvents::POST_SAVE => 'onNotificationSaved',
        ];
    }

    public function onNotificationSaved(NotificationEvent $event): void
    {
        $notification = $event->getNotification();
        if ($notification->isRead()) {
            return;
        }

        $this->publishService->publish(
            Topics::STUDIO->value,
            [
                'unreadNotificationsCount' => $this->notificationRepository->getUnreadCountByUser(
                    $notification->getRecipient()
                ),
                'notification' => $this->notificationHydrator->hydrateMinimal($notification),
            ]
        );
    }
}
