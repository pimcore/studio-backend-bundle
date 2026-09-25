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
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\SubscriptionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\SubscriptionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\GeneralNotificationType;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\NotificationType;
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
        $availableChannelIds = $this->availableChannelIds();

        $items = [];
        foreach ($this->typeRegistry->getTypes() as $type) {
            $typeId = $type->getTypeId();

            $items[] = $this->subscriptionHydrator->hydrate(
                $type,
                $effective[$typeId],
                $availableChannelIds,
                $this->channelRegistry->getSupportedChannelIds($type),
                $this->resolveTranslationKey($type),
                $this->resolveDescriptionKey($type)
            );
        }

        $collection = new SubscriptionCollection(
            array_map(
                fn (string $channelId): AvailableChannel => new AvailableChannel(
                    $channelId,
                    self::CHANNEL_TRANSLATION_PREFIX . $channelId,
                    $this->channelRegistry->getEnabledChannel($channelId)?->unavailableReasonFor($user)
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
            $type = $this->resolveType($item->getTypeId());
            $subscribed = $this->resolveSubscribed($type, $item->isSubscribed());

            $preferences[$item->getTypeId()] = [
                'subscribed' => $subscribed,
                'channels' => $this->resolveChannels(
                    $type,
                    $subscribed,
                    $item->getChannels(),
                    ($stored[$item->getTypeId()] ?? null)?->getChannels()
                ),
            ];
        }

        $this->subscriptionRepository->save($userId, $preferences);

        return $this->getSubscriptions($user);
    }

    /**
     * A bad request-body field, so the bundle's InvalidArgumentException (422) rather than
     * the registry's NotFoundException (404).
     *
     * @throws InvalidArgumentException
     */
    private function resolveType(string $typeId): NotificationType
    {
        if (!$this->typeRegistry->hasType($typeId)) {
            throw new InvalidArgumentException(
                sprintf('Unknown notification type "%s".', $typeId)
            );
        }

        return $this->typeRegistry->getType($typeId);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function resolveSubscribed(
        NotificationType $type,
        bool $requested,
    ): bool {
        if ($type->isSubscriptionLocked() && !$requested) {
            throw new InvalidArgumentException(
                sprintf(
                    'Notification type "%s" cannot be unsubscribed from.',
                    $type->getTypeId()
                )
            );
        }

        return $type->isSubscriptionLocked() ? true : $requested;
    }

    /**
     * A channel the type cannot use, or the installation no longer offers, is dropped rather
     * than rejected — an admin disabling one mid-edit must not fail the whole bulk save.
     * Stored ids the installation currently does not offer are preserved: the client never
     * saw them, so they are not the client's to clear.
     *
     * @param string[] $requested
     * @param string[]|null $previouslyStored null when the user has never chosen for this type
     *
     * @return string[]
     */
    private function resolveChannels(
        NotificationType $type,
        bool $subscribed,
        array $requested,
        ?array $previouslyStored,
    ): array {
        $availableChannelIds = $this->availableChannelIds();

        $unresolvable = array_filter(
            $previouslyStored ?? [],
            static fn (string $channel): bool => !in_array($channel, $availableChannelIds, true)
        );

        // switching a type off clears the channels the client can see
        if (!$subscribed) {
            return array_values($unresolvable);
        }

        $supported = $this->channelRegistry->getSupportedChannelIds($type);

        $kept = array_filter(
            $requested,
            static fn (string $channel): bool => in_array($channel, $supported, true)
        );

        $this->logDroppedChannels($type->getTypeId(), $requested, $kept);

        return array_values(array_unique([...$kept, ...$unresolvable]));
    }

    /**
     * With no externally-deliverable type there is nothing a transport could ever carry, so
     * the preferences screen gets no channel columns — instead of a column of dead switches.
     *
     * @return string[]
     */
    private function availableChannelIds(): array
    {
        if (!$this->typeRegistry->hasExternallyDeliverableType()) {
            return [ChannelRegistryInterface::POPUP_CHANNEL];
        }

        return $this->channelRegistry->getAvailableChannelIds();
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
     * The catch-all is "everything else" only when there is something else.
     */
    private function resolveTranslationKey(NotificationType $type): string
    {
        if ($this->isSoloGeneral($type)) {
            return GeneralNotificationType::SOLO_TRANSLATION_KEY;
        }

        return $type->getTranslationKey();
    }

    private function resolveDescriptionKey(NotificationType $type): string
    {
        if ($this->isSoloGeneral($type)) {
            return GeneralNotificationType::SOLO_DESCRIPTION_KEY;
        }

        return $type->getDescriptionKey();
    }

    private function isSoloGeneral(NotificationType $type): bool
    {
        return $type->getTypeId() === GeneralNotificationType::TYPE_ID
            && $this->typeRegistry->hasOnlyGeneralType();
    }
}
