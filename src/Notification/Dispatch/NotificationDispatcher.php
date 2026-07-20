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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\SubscriptionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\Notification;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use function json_encode;
use function sprintf;
use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
final readonly class NotificationDispatcher implements NotificationDispatcherInterface
{
    public function __construct(
        private NotificationTypeRegistryInterface $typeRegistry,
        private ChannelRegistryInterface $channelRegistry,
        private SubscriptionResolverInterface $subscriptionResolver,
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
                $recipient = $this->userRepository->getUserById($recipientId);
            } catch (NotFoundException) {
                continue;
            }

            if (!$recipient->isAllowed(UserPermissions::NOTIFICATIONS->value)) {
                continue;
            }

            $subscription = $this->subscriptionResolver->resolve($recipientId, $descriptor);

            if (!$subscription->isSubscribed()) {
                continue;
            }

            $stored = $this->write($notification, $recipient, $sender);

            $this->deliver($stored, $recipient, $subscription->getTransportChannels());
        }
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
     * @throws DatabaseException
     */
    private function write(
        DispatchableNotification $notification,
        UserInterface $recipient,
        ?UserInterface $sender,
    ): Notification {
        try {
            $stored = new Notification();
            /** @var User $recipient */
            $stored->setRecipient($recipient);
            /** @var User|null $sender */
            $stored->setSender($sender);
            $stored->setType($notification->getTypeId());
            $stored->setTitle($notification->getTitle());
            $stored->setMessage($notification->getMessage());
            $stored->setLinkedElement($notification->getLinkedElement());
            $stored->setPayload(json_encode($notification->getPayload(), JSON_THROW_ON_ERROR));
            $stored->setIsStudio(true);
            $stored->save();

            return $stored;
        } catch (Exception $e) {
            throw new DatabaseException(
                sprintf('Failed to write notification: %s', $e->getMessage()),
                $e
            );
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
