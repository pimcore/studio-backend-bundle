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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Email\Util\Constants\TestEmailContentType;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;

#[Schema(
    title: 'TestEmailRequest',
    required: ['from', 'to', 'subject', 'contentType'],
    type: 'object'
)]
final readonly class TestEmailRequest
{
    public function __construct(
        #[Property(description: 'from email address(es)', type: 'string', example: 'from@sender.com')]
        private string $from,
        #[Property(description: 'to email address(es)', type: 'string', example: 'to@receiver.com')]
        private string $to,
        #[Property(description: 'email subject', type: 'string', example: 'My email subject')]
        private string $subject,
        #[Property(
            description: 'email content type',
            type: 'enum',
            enum: [
                TestEmailContentType::DOCUMENT->value,
                TestEmailContentType::HTML->value,
                TestEmailContentType::TEXT->value
            ],
            example: TestEmailContentType::TEXT->value
        )]
        private string $contentType,
        #[Property(description: 'email content', type: 'string', example: 'My email message')]
        private ?string $content = null,
        #[Property(description: 'path to the email document', type: 'string', example: '/path/to/document')]
        private ?string $documentPath = null,
        #[Property(
            description: 'email document parameters',
            type: 'array',
            items: new Items(ref: EmailDocumentParameters::class)
        )]
        private array $documentParameters = [],
        #[Property(description: 'id of the asset attachment', type: 'int', example: 83)]
        private ?int $attachmentId = null,

    ) {
        $this->validateContentParameters();
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getDocumentPath(): ?string
    {
        return $this->documentPath;
    }

    /**
     * @return EmailDocumentParameters[]
     */
    public function getDocumentParameters(): array
    {
        return $this->documentParameters;
    }

    public function getAttachmentId(): ?int
    {
        return $this->attachmentId;
    }

    private function validateContentParameters(): void
    {
        match (true) {
            ($this->contentType === TestEmailContentType::TEXT->value ||
                $this->contentType === TestEmailContentType::HTML->value) &&
            $this->content === null =>
            throw new EnvironmentException('Content is required for text and HTML emails'),
            $this->contentType === TestEmailContentType::DOCUMENT->value && $this->documentPath === null =>
            throw new EnvironmentException('Document path is required for document emails'),
            !in_array($this->contentType, [
                TestEmailContentType::DOCUMENT->value,
                TestEmailContentType::HTML->value,
                TestEmailContentType::TEXT->value
            ], true) =>
            throw new EnvironmentException('Invalid content type'),
            default => null
        };
    }
}
