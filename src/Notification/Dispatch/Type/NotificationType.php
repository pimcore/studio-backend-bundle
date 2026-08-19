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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type;

use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistryInterface;

/**
 * One subscribable notification type. Plain data — the constructor defaults are the framework
 * defaults, so a contributing bundle only states what is specific to its type.
 *
 * @internal
 */
final readonly class NotificationType
{
    /**
     * @param string $typeId dotted, stable, at most 20 characters (the `notifications`.`type`
     *                       column); persisted in notification and subscription rows, so
     *                       renaming is a breaking change
     * @param string $translationKey label for the preferences row
     * @param string $group grouping key for the preferences screen; ship a
     *                      `notifications.settings.group.<group>` translation for it
     * @param bool $allowsExternalDelivery whether the type may leave the application at all
     * @param string[] $defaultChannels channels enabled before the user has chosen
     * @param bool $subscriptionLocked true for types a user must not switch off entirely
     */
    public function __construct(
        private string $typeId,
        private string $translationKey,
        private string $descriptionKey,
        private string $group,
        private int $sortOrder = 100,
        private bool $allowsExternalDelivery = false,
        private array $defaultChannels = [ChannelRegistryInterface::POPUP_CHANNEL],
        private bool $subscribedByDefault = true,
        private bool $subscriptionLocked = false,
    ) {
    }

    public function getTypeId(): string
    {
        return $this->typeId;
    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    public function getDescriptionKey(): string
    {
        return $this->descriptionKey;
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

    /**
     * @return string[]
     */
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
