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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture;

use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\AbstractNotificationTypeDescriptor;

/**
 * Stands in for a type contributed by another bundle.
 */
final class TestNotificationTypeDescriptor extends AbstractNotificationTypeDescriptor
{
    /**
     * @param string[] $defaultChannels
     */
    public function __construct(
        private readonly string $typeId,
        private readonly bool $allowsExternalDelivery = false,
        private readonly array $defaultChannels = ['popup'],
        private readonly bool $subscribedByDefault = true,
        private readonly bool $subscriptionLocked = false,
        private readonly int $sortOrder = 100,
        private readonly string $group = 'test',
    ) {
    }

    public function getTypeId(): string
    {
        return $this->typeId;
    }

    public function getTranslationKey(): string
    {
        return $this->typeId . '.label';
    }

    public function getDescriptionKey(): string
    {
        return $this->typeId . '.description';
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function allowsExternalDelivery(): bool
    {
        return $this->allowsExternalDelivery;
    }

    public function getDefaultChannels(): array
    {
        return $this->defaultChannels;
    }

    public function isSubscribedByDefault(): bool
    {
        return $this->subscribedByDefault;
    }

    public function isSubscriptionLocked(): bool
    {
        return $this->subscriptionLocked;
    }
}
