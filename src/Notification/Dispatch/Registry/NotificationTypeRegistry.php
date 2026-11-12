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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidNotificationTypeException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\GeneralNotificationType;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\NotificationType;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\NotificationTypeProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use function count;
use function sprintf;
use function strlen;

/**
 * @internal
 */
final readonly class NotificationTypeRegistry implements NotificationTypeRegistryInterface
{
    /**
     * @var array<string, NotificationType>
     */
    private array $types;

    /**
     * @param iterable<NotificationTypeProviderInterface> $providers
     *
     * @throws InvalidNotificationTypeException
     */
    public function __construct(
        #[AutowireIterator(NotificationTypeProviderInterface::TAG)]
        iterable $providers,
    ) {
        $this->types = $this->collect($providers);
    }

    public function getTypes(): array
    {
        return array_values($this->types);
    }

    public function getType(string $typeId): NotificationType
    {
        if (!isset($this->types[$typeId])) {
            throw new NotFoundException('Notification type', $typeId, 'type id');
        }

        return $this->types[$typeId];
    }

    public function hasType(string $typeId): bool
    {
        return isset($this->types[$typeId]);
    }

    public function resolveBucket(?string $typeId): NotificationType
    {
        if ($typeId === null || $typeId === '') {
            return $this->types[GeneralNotificationType::TYPE_ID];
        }

        return $this->types[$typeId] ?? $this->types[GeneralNotificationType::TYPE_ID];
    }

    public function hasOnlyGeneralType(): bool
    {
        return count($this->types) === 1;
    }

    public function hasExternallyDeliverableType(): bool
    {
        foreach ($this->types as $type) {
            if ($type->allowsExternalDelivery()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param iterable<NotificationTypeProviderInterface> $providers
     *
     * @return array<string, NotificationType>
     *
     * @throws InvalidNotificationTypeException
     */
    private function collect(iterable $providers): array
    {
        // built directly, not provided: the catch-all's presence must not depend on wiring
        $types = [GeneralNotificationType::TYPE_ID => GeneralNotificationType::create()];

        foreach ($providers as $provider) {
            foreach ($provider->getTypes() as $type) {
                $typeId = $type->getTypeId();

                if (strlen($typeId) > self::MAX_TYPE_ID_LENGTH) {
                    throw new InvalidNotificationTypeException(
                        sprintf(
                            'Notification type id "%s" is %d characters; the notifications.type ' .
                            'column allows at most %d.',
                            $typeId,
                            strlen($typeId),
                            self::MAX_TYPE_ID_LENGTH
                        )
                    );
                }

                if (isset($types[$typeId])) {
                    throw new InvalidNotificationTypeException(
                        sprintf('Notification type id "%s" is registered more than once.', $typeId)
                    );
                }

                $types[$typeId] = $type;
            }
        }

        uasort(
            $types,
            static fn (NotificationType $a, NotificationType $b): int
                => [$a->getSortOrder(), $a->getTypeId()] <=> [$b->getSortOrder(), $b->getTypeId()]
        );

        return $types;
    }
}
