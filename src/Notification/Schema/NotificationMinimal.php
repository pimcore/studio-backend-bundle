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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'NotificationMinimal',
    title: 'Notification Minimal Data',
    required: ['id', 'type', 'title', 'read', 'creationDate', 'recipient', 'sender'],
    type: 'object'
)]
final readonly class NotificationMinimal
{
    public function __construct(
        #[Property(description: 'id', type: 'int', example: 23)]
        private int $id,
        #[Property(description: 'type', type: 'string', example: 'info')]
        private string $type,
        #[Property(description: 'title', type: 'string', example: 'Notification title')]
        private string $title,
        #[Property(description: 'read', type: 'bool', example: false)]
        private bool $read,
        #[Property(description: 'creation date', type: 'integer', example: 1707312457)]
        private int $creationDate,
        #[Property(description: 'recipient ID', type: 'integet', example: 1)]
        private int $recipient,
        #[Property(description: 'sender', type: 'string', example: 'Pimcore Admin')]
        private ?string $sender = null,
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function isRead(): bool
    {
        return $this->read;
    }

    public function getCreationDate(): int
    {
        return $this->creationDate;
    }

    public function getRecipient(): int
    {
        return $this->recipient;
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }
}
