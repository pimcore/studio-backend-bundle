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
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\GeneralNotificationDescriptor;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\NotificationTypeDescriptorInterface;
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
     * @var array<string, NotificationTypeDescriptorInterface>
     */
    private array $descriptors;

    /**
     * @param iterable<NotificationTypeDescriptorInterface> $taggedDescriptors
     *
     * @throws InvalidNotificationTypeException
     */
    public function __construct(
        #[AutowireIterator(NotificationTypeDescriptorInterface::TAG)]
        iterable $taggedDescriptors,
        private GeneralNotificationDescriptor $generalDescriptor,
    ) {
        $this->descriptors = $this->collect($taggedDescriptors);
    }

    public function getDescriptors(): array
    {
        return array_values($this->descriptors);
    }

    public function getDescriptor(string $typeId): NotificationTypeDescriptorInterface
    {
        if (!isset($this->descriptors[$typeId])) {
            throw new NotFoundException('Notification type', $typeId, 'type id');
        }

        return $this->descriptors[$typeId];
    }

    public function hasDescriptor(string $typeId): bool
    {
        return isset($this->descriptors[$typeId]);
    }

    public function resolveBucket(?string $typeId): NotificationTypeDescriptorInterface
    {
        if ($typeId === null || $typeId === '') {
            return $this->generalDescriptor;
        }

        return $this->descriptors[$typeId] ?? $this->generalDescriptor;
    }

    public function hasOnlyGeneralDescriptor(): bool
    {
        return count($this->descriptors) === 1;
    }

    public function hasExternallyDeliverableType(): bool
    {
        foreach ($this->descriptors as $descriptor) {
            if ($descriptor->allowsExternalDelivery()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param iterable<NotificationTypeDescriptorInterface> $taggedDescriptors
     *
     * @return array<string, NotificationTypeDescriptorInterface>
     *
     * @throws InvalidNotificationTypeException
     */
    private function collect(iterable $taggedDescriptors): array
    {
        $descriptors = [];

        // added directly, not by tag: the catch-all's presence must not depend on wiring
        $descriptors[$this->generalDescriptor->getTypeId()] = $this->generalDescriptor;

        foreach ($taggedDescriptors as $descriptor) {
            if ($descriptor === $this->generalDescriptor) {
                continue;
            }

            $typeId = $descriptor->getTypeId();

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

            if (isset($descriptors[$typeId])) {
                throw new InvalidNotificationTypeException(
                    sprintf('Notification type id "%s" is registered more than once.', $typeId)
                );
            }

            $descriptors[$typeId] = $descriptor;
        }

        uasort(
            $descriptors,
            static fn (
                NotificationTypeDescriptorInterface $a,
                NotificationTypeDescriptorInterface $b
            ): int => [$a->getSortOrder(), $a->getTypeId()] <=> [$b->getSortOrder(), $b->getTypeId()]
        );

        return $descriptors;
    }
}
