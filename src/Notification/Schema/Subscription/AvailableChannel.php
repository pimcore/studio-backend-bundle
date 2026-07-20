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
}
