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
use Symfony\Component\Validator\Constraints as Assert;

#[Schema(
    schema: 'NotificationUpdateSubscriptionItem',
    title: 'Notification Update Subscription Item',
    required: ['typeId', 'subscribed', 'channels'],
    type: 'object'
)]
final readonly class UpdateSubscriptionItem
{
    /**
     * @param string[] $channels
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Property(description: 'notification type id', type: 'string', example: 'info')]
        private string $typeId,
        #[Property(description: 'whether the user wants this type at all', type: 'bool', example: true)]
        private bool $subscribed,
        #[Assert\All([new Assert\Type('string')])]
        #[Property(
            description: 'enabled channel ids',
            type: 'array',
            items: new Items(type: 'string')
        )]
        private array $channels = [],
    ) {
    }

    public function getTypeId(): string
    {
        return $this->typeId;
    }

    public function isSubscribed(): bool
    {
        return $this->subscribed;
    }

    /**
     * @return string[]
     */
    public function getChannels(): array
    {
        return $this->channels;
    }
}
