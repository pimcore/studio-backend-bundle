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

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'NotificationAvailableChannel',
    title: 'Notification Available Channel',
    required: ['id', 'translationKey'],
    type: 'object'
)]
final readonly class AvailableChannel
{
    public function __construct(
        #[Property(description: 'channel id', type: 'string', example: 'popup')]
        private string $id,
        #[Property(description: 'translation key for the column label', type: 'string')]
        private string $translationKey,
        /**
         * Set when the channel cannot reach this user at all — an email channel with no address on
         * the account, say. The switch still stores, so the screen explains rather than hides it.
         */
        #[Property(
            description: 'translation key explaining why this channel cannot reach the caller, null when it can',
            type: 'string',
            nullable: true,
            example: 'notifications.channel.email.no-address'
        )]
        private ?string $unavailableReasonKey = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    public function getUnavailableReasonKey(): ?string
    {
        return $this->unavailableReasonKey;
    }
}
