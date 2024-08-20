<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'NotificationListItem',
    required: ['id', 'type', 'title', 'read', 'hasAttachment', 'sentDate', 'sender'],
    type: 'object'
)]
class NotificationListItem implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'id', type: 'int', example: 23)]
        private readonly int $id,
        #[Property(description: 'type', type: 'string', example: 'info')]
        private readonly string $type,
        #[Property(description: 'title', type: 'string', example: 'Notification title')]
        private readonly string $title,
        #[Property(description: 'read', type: 'bool', example: false)]
        private readonly bool $read,
        #[Property(description: 'has attachment', type: 'bool', example: true)]
        private readonly bool $hasAttachment,
        #[Property(description: 'sent date', type: 'integer', example: 1707312457)]
        private readonly int $sentDate,
        #[Property(description: 'sender', type: 'string', example: 'Pimcore Admin')]
        private readonly ?string $sender = null,
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

    public function hasAttachment(): bool
    {
        return $this->hasAttachment;
    }

    public function getSentDate(): int
    {
        return $this->sentDate;
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }
}
