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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * One row of the notification preferences screen: a type, what the user has chosen for it, and
 * a cell per available channel.
 */
#[Schema(
    schema: 'NotificationSubscribableType',
    title: 'Notification Subscribable Type',
    required: [
        'typeId',
        'translationKey',
        'descriptionKey',
        'group',
        'sortOrder',
        'subscribed',
        'subscriptionLocked',
        'channels',
    ],
    type: 'object'
)]
final readonly class SubscribableType
{
    /**
     * @param SubscriptionChannel[] $channels
     */
    public function __construct(
        #[Property(description: 'notification type id', type: 'string', example: 'info')]
        private string $typeId,
        /**
         * Already resolved: the general catch-all reports a different key when it is the only
         * registered type, so the frontend renders what it is given and stays unaware of the
         * rule.
         */
        #[Property(description: 'translation key for the row label', type: 'string')]
        private string $translationKey,
        #[Property(description: 'translation key for the row description', type: 'string')]
        private string $descriptionKey,
        #[Property(description: 'grouping key', type: 'string', example: 'general')]
        private string $group,
        #[Property(description: 'explicit order; never rely on registration order', type: 'integer')]
        private int $sortOrder,
        #[Property(description: 'whether the user is subscribed', type: 'bool', example: true)]
        private bool $subscribed,
        /**
         * True for types a user must not be able to switch off — the catch-all, so that no
         * preference can make a notification vanish without trace.
         */
        #[Property(description: 'whether the subscription cannot be turned off', type: 'bool', example: false)]
        private bool $subscriptionLocked,
        #[Property(
            description: 'one entry per available channel',
            type: 'array',
            items: new Items(ref: SubscriptionChannel::class)
        )]
        private array $channels,
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

    public function isSubscribed(): bool
    {
        return $this->subscribed;
    }

    public function isSubscriptionLocked(): bool
    {
        return $this->subscriptionLocked;
    }

    /**
     * @return SubscriptionChannel[]
     */
    public function getChannels(): array
    {
        return $this->channels;
    }
}
