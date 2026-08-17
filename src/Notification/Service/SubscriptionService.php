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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\GeneralNotificationDescriptor;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\NotificationTypeDescriptorInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\SubscriptionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\SubscriptionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Event\SubscriptionCollectionEvent;
use Pimcore\Bundle\StudioBackendBundle\Notification\Hydrator\SubscriptionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\AvailableChannel;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\SubscriptionCollection;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\UpdateSubscriptionsParameters;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function array_diff;
use function array_values;
use function implode;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class SubscriptionService implements SubscriptionServiceInterface
{
    private const string CHANNEL_TRANSLATION_PREFIX = 'notifications.channel.';

    public function __construct(
        private NotificationTypeRegistryInterface $typeRegistry,
        private ChannelRegistryInterface $channelRegistry,
        private SubscriptionResolverInterface $subscriptionResolver,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private SubscriptionHydratorInterface $subscriptionHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function getSubscriptions(UserInterface $user): SubscriptionCollection
    {
        $effective = $this->subscriptionResolver->resolveAll($user->getId());
        $availableChannelIds = $this->channelRegistry->getAvailableChannelIds();

        $items = [];
        foreach ($this->typeRegistry->getDescriptors() as $descriptor) {
            $typeId = $descriptor->getTypeId();

            $items[] = $this->subscriptionHydrator->hydrate(
                $descriptor,
                $effective[$typeId],
                $availableChannelIds,
                $this->channelRegistry->getSupportedChannelIds($descriptor),
                $this->resolveTranslationKey($descriptor),
                $this->resolveDescriptionKey($descriptor)
            );
        }

        $collection = new SubscriptionCollection(
            array_map(
                static fn (string $channelId): AvailableChannel => new AvailableChannel(
                    $channelId,
                    self::CHANNEL_TRANSLATION_PREFIX . $channelId
                ),
                $availableChannelIds
            ),
            $items
        );

        $this->eventDispatcher->dispatch(
            new SubscriptionCollectionEvent($collection),
            SubscriptionCollectionEvent::EVENT_NAME
        );

        return $collection;
    }

    public function updateSubscriptions(
        UserInterface $user,
        UpdateSubscriptionsParameters $parameters
    ): SubscriptionCollection {
        $userId = $user->getId();
        $stored = $this->subscriptionRepository->getByUser($userId);

        $preferences = [];
        foreach ($parameters->getItems() as $item) {
            $descriptor = $this->resolveDescriptor($item->getTypeId());
            $subscribed = $this->resolveSubscribed($descriptor, $item->isSubscribed());

            $preferences[$item->getTypeId()] = [
                'subscribed' => $subscribed,
                'channels' => $this->resolveChannels(
                    $descriptor,
                    $subscribed,
                    $item->getChannels(),
                    ($stored[$item->getTypeId()] ?? null)?->getChannels() ?? []
                ),
            ];
        }

        $this->subscriptionRepository->save($userId, $preferences);

        return $this->getSubscriptions($user);
    }

    /**
     * A type id the server does not know is not a race an administrator could have caused, and
     * returning the stored state cannot repair a client asking about something that has never
     * existed — so unlike an unavailable channel this is rejected. It is a bad field in a request
     * body rather than a missing resource, hence 400 rather than the registry's 404.
     *
     * @throws InvalidArgumentException
     */
    private function resolveDescriptor(string $typeId): NotificationTypeDescriptorInterface
    {
        if (!$this->typeRegistry->hasDescriptor($typeId)) {
            throw new InvalidArgumentException(
                sprintf('Unknown notification type "%s".', $typeId)
            );
        }

        return $this->typeRegistry->getDescriptor($typeId);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function resolveSubscribed(
        NotificationTypeDescriptorInterface $descriptor,
        bool $requested,
    ): bool {
        if ($descriptor->isSubscriptionLocked() && !$requested) {
            throw new InvalidArgumentException(
                sprintf(
                    'Notification type "%s" cannot be unsubscribed from.',
                    $descriptor->getTypeId()
                )
            );
        }

        return $descriptor->isSubscriptionLocked() ? true : $requested;
    }

    /**
     * Two things happen here that the client cannot be trusted to do.
     *
     * A channel the type cannot use — or that the installation no longer offers at all — is
     * dropped rather than rejected. An administrator may disable a channel, or a bundle
     * providing one may be uninstalled, between the screen loading and the user saving; that is
     * a race, not a client error, and failing the whole bulk save over it would cost the user
     * every other row. The endpoint returns the stored state, so what was dropped is visible in
     * the response rather than silent — and it is logged for anyone debugging a client.
     *
     * Channel ids that are currently unresolvable are preserved. A bundle providing a channel
     * may be temporarily disabled, in which case the client never saw that id — replacing the
     * set wholesale would silently discard a preference the user still holds.
     *
     * @param string[] $requested
     * @param string[] $previouslyStored
     *
     * @return string[]
     */
    private function resolveChannels(
        NotificationTypeDescriptorInterface $descriptor,
        bool $subscribed,
        array $requested,
        array $previouslyStored,
    ): array {
        if (!$subscribed) {
            return [];
        }

        $availableChannelIds = $this->channelRegistry->getAvailableChannelIds();
        $supported = $this->channelRegistry->getSupportedChannelIds($descriptor);

        $kept = array_filter(
            $requested,
            static fn (string $channel): bool => in_array($channel, $supported, true)
        );

        $this->logDroppedChannels($descriptor->getTypeId(), $requested, $kept);

        $unresolvable = array_filter(
            $previouslyStored,
            static fn (string $channel): bool => !in_array($channel, $availableChannelIds, true)
        );

        return array_values(array_unique([...$kept, ...$unresolvable]));
    }

    /**
     * @param string[] $requested
     * @param string[] $kept
     */
    private function logDroppedChannels(string $typeId, array $requested, array $kept): void
    {
        $dropped = array_diff($requested, $kept);

        if ($dropped === []) {
            return;
        }

        $this->logger->warning(
            sprintf(
                'Dropped notification channel(s) "%s" requested for type "%s": not offered by ' .
                'this installation, or not usable by that type.',
                implode('", "', $dropped),
                $typeId
            )
        );
    }

    /**
     * The catch-all is "everything else" only when there is something else. On its own it is
     * simply all notifications, and says so.
     */
    private function resolveTranslationKey(NotificationTypeDescriptorInterface $descriptor): string
    {
        if ($descriptor instanceof GeneralNotificationDescriptor && $this->typeRegistry->hasOnlyGeneralDescriptor()) {
            return $descriptor->getSoloTranslationKey();
        }

        return $descriptor->getTranslationKey();
    }

    private function resolveDescriptionKey(NotificationTypeDescriptorInterface $descriptor): string
    {
        if ($descriptor instanceof GeneralNotificationDescriptor && $this->typeRegistry->hasOnlyGeneralDescriptor()) {
            return $descriptor->getSoloDescriptionKey();
        }

        return $descriptor->getDescriptionKey();
    }
}
