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

/**
 * One channel cell for one notification type. Present for every available channel so the
 * frontend can render a complete row without cross-referencing.
 */
#[Schema(
    schema: 'NotificationSubscriptionChannel',
    title: 'Notification Subscription Channel',
    required: ['id', 'enabled', 'supported'],
    type: 'object'
)]
final readonly class SubscriptionChannel
{
    public function __construct(
        #[Property(description: 'channel id', type: 'string', example: 'popup')]
        private string $id,
        #[Property(description: 'whether the user has this channel enabled', type: 'bool', example: true)]
        private bool $enabled,
        /**
         * False when the type does not allow delivery through this channel at all. Renders as
         * "not applicable" rather than a disabled switch — a lock would imply an administrator
         * could unlock it, and invite a support request for something structurally impossible.
         */
        #[Property(description: 'whether this type can use this channel', type: 'bool', example: true)]
        private bool $supported,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isSupported(): bool
    {
        return $this->supported;
    }
}
