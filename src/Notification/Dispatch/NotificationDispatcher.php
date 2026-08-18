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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\NotificationTypeDescriptorInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\SubscriptionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\Notification;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class NotificationDispatcher implements NotificationDispatcherInterface
{
    public function __construct(
        private NotificationTypeRegistryInterface $typeRegistry,
        private ChannelRegistryInterface $channelRegistry,
        private SubscriptionResolverInterface $subscriptionResolver,
        private NotificationWriterInterface $notificationWriter,
        private UserRepositoryInterface $userRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function dispatch(DispatchableNotification $notification): void
    {
        // Unregistered type is a programming error in the producing bundle, so it surfaces
        // rather than silently writing an unroutable notification.
        $descriptor = $this->typeRegistry->getDescriptor($notification->getTypeId());

        $sender = $this->resolveSender($notification->getSenderId());

        foreach ($notification->getRecipientIds() as $recipientId) {
            try {
                $this->dispatchToRecipient($notification, $descriptor, $recipientId, $sender);
            } catch (Exception $e) {
                // Recipients are independent: one failing row must not cost the others theirs.
                $this->logger->error(
                    sprintf(
                        'Notification could not be dispatched to user %d: %s',
                        $recipientId,
                        $e->getMessage()
                    ),
                    ['exception' => $e]
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    private function dispatchToRecipient(
        DispatchableNotification $notification,
        NotificationTypeDescriptorInterface $descriptor,
        int $recipientId,
        ?UserInterface $sender,
    ): void {
        try {
            $recipient = $this->userRepository->getUserById($recipientId);
        } catch (NotFoundException) {
            return;
        }

        if (!$recipient->isAllowed(UserPermissions::NOTIFICATIONS->value)) {
            return;
        }

        $subscription = $this->subscriptionResolver->resolve($recipientId, $descriptor);

        if (!$subscription->isSubscribed()) {
            return;
        }

        $stored = $this->notificationWriter->write($notification, $recipient, $sender);

        $this->deliver($stored, $recipient, $subscription->getTransportChannels());
    }

    private function resolveSender(?int $senderId): ?UserInterface
    {
        if ($senderId === null) {
            return null;
        }

        try {
            return $this->userRepository->getUserById($senderId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * A channel that throws is logged and skipped. External delivery is best effort by
     * design: it must never break the action that produced the notification, and one broken
     * transport must not stop the others.
     *
     * @param string[] $channelIds
     */
    private function deliver(Notification $notification, UserInterface $recipient, array $channelIds): void
    {
        foreach ($channelIds as $channelId) {
            $channel = $this->channelRegistry->getEnabledChannel($channelId);

            if ($channel === null) {
                continue;
            }

            try {
                $channel->send($notification, $recipient);
            } catch (Exception $e) {
                $this->logger->error(
                    sprintf(
                        'Notification channel "%s" failed for user %d: %s',
                        $channelId,
                        $recipient->getId(),
                        $e->getMessage()
                    ),
                    ['exception' => $e]
                );
            }
        }
    }
}
