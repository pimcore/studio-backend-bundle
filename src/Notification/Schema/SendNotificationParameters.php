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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use function in_array;

#[Schema(
    title: 'SendEmailParameters',
    required: ['recipientId', 'title', 'message'],
    type: 'object'
)]
final readonly class SendNotificationParameters
{
    public function __construct(
        #[Property(description: 'recipient ID', type: 'integer', example: '33')]
        private int $recipientId,
        #[Property(description: 'title', type: 'string', example: 'New notification')]
        private string $title,
        #[Property(description: 'message', type: 'string', example: 'My notification message')]
        private string $message,
        #[Property(
            description: 'type of the attachment',
            type: 'enum',
            enum: [
                ElementTypes::TYPE_ASSET,
                ElementTypes::TYPE_DOCUMENT,
                ElementTypes::TYPE_OBJECT,
            ],
            example: null
        )]
        private ?string $attachmentType = null,
        #[Property(description: 'ID of the attachment', type: 'int', example: 83)]
        private ?int $attachmentId = null,

    ) {
        $this->validateElementType();
    }

    public function getRecipientId(): int
    {
        return $this->recipientId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getAttachmentType(): ?string
    {
        return $this->attachmentType;
    }

    public function getAttachmentId(): ?int
    {
        return $this->attachmentId;
    }

    private function validateElementType(): void
    {
        if ($this->attachmentType !== null &&
            !in_array($this->attachmentType, ElementTypes::ALLOWED_TYPES, true)
        ) {
            throw new EnvironmentException('Invalid attachment type');
        }
    }
}
