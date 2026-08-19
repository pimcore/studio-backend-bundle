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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Topics;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\SubscriptionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Hydrator\NotificationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Repository\NotificationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StudioBackendPathTrait;
use Pimcore\Event\Model\NotificationEvent;
use Pimcore\Event\NotificationEvents;
use Pimcore\Model\Notification;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function sprintf;

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
        private NotificationTypeRegistryInterface $typeRegistry,
        private SubscriptionResolverInterface $subscriptionResolver,
        private LoggerInterface $logger,
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
                'notification' => $this->notificationHydrator->hydrateMinimal(
                    $notification,
                    $this->wantsPopup($notification)
                ),
            ]
        );
    }

    /**
     * Resolved here rather than by the producer, so every existing producer gets a working
     * pop-up toggle untouched. Any resolution error falls back to showing the toast — the
     * behaviour before this setting existed.
     */
    private function wantsPopup(Notification $notification): bool
    {
        $recipientId = $notification->getRecipient()?->getId();

        if ($recipientId === null) {
            return true;
        }

        try {
            return $this->subscriptionResolver->resolve(
                $recipientId,
                $this->typeRegistry->resolveBucket($notification->getType())
            )->wantsPopup();
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'Could not resolve the notification pop-up preference for user %d, ' .
                    'defaulting to showing it: %s',
                    $recipientId,
                    $e->getMessage()
                ),
                ['exception' => $e]
            );

            return true;
        }
    }
}
