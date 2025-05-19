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
    title: 'Notification',
    required: ['message', 'payload', 'attachmentType', 'attachmentId'],
    type: 'object'
)]
final class Notification extends NotificationListItem
{
    public function __construct(
        int $id,
        string $type,
        string $title,
        bool $read,
        bool $hasAttachment,
        int $sentDate,
        ?string $sender = null,
        #[Property(description: 'message', type: 'string', example: 'Notification message')]
        private readonly ?string $message = null,
        #[Property(description: 'payload', type: 'string', example: '{"key": "value"}')]
        private readonly ?string $payload = null,
        #[Property(description: 'linked attachment type', type: 'string', example: 'object')]
        private readonly ?string $attachmentType = null,
        #[Property(description: 'linked attachment ID', type: 'integer', example: 3669)]
        private readonly ?int $attachmentId = null,
    ) {
        parent::__construct($id, $type, $title, $read, $hasAttachment, $sentDate, $sender);
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function getAttachmentType(): ?string
    {
        return $this->attachmentType;
    }

    public function getAttachmentId(): ?int
    {
        return $this->attachmentId;
    }
}
