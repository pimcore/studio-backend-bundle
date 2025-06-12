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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema\Settings;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Email Settings Data',
    required: ['subject', 'from', 'replyTo', 'to', 'cc', 'bcc'],
    type: 'object'
)]
final readonly class EmailSettingsData extends SnippetSettingsData
{
    public function __construct(
        #[Property(description: 'Subject', type: 'string', example: 'Some subject')]
        private string $subject,
        #[Property(description: 'From', type: 'string', example: 'some-sender@email')]
        private string $from,
        #[Property(description: 'Reply to', type: 'string', example: 'some-reply@email')]
        private string $replyTo,
        #[Property(description: 'To', type: 'string', example: 'some-receiver@email')]
        private string $to,
        #[Property(description: 'CC', type: 'string', example: 'some-copy@email')]
        private string $cc,
        #[Property(description: 'BCC', type: 'string', example: 'some-hidden-copy@email')]
        private string $bcc,
        ?string $controller,
        ?string $template,
        ?int $contentMainDocumentId,
        ?string $contentMainDocumentPath,
        bool $supportsContentMain,
        bool $staticGeneratorEnabled,
        ?int $staticGeneratorLifetime,
        ?string $url,
    ) {
        parent::__construct(
            controller: $controller,
            template: $template,
            contentMainDocumentId: $contentMainDocumentId,
            contentMainDocumentPath: $contentMainDocumentPath,
            supportsContentMain: $supportsContentMain,
            staticGeneratorEnabled: $staticGeneratorEnabled,
            staticGeneratorLifetime: $staticGeneratorLifetime,
            url: $url
        );
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getReplyTo(): string
    {
        return $this->replyTo;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getCc(): string
    {
        return $this->cc;
    }

    public function getBcc(): string
    {
        return $this->bcc;
    }
}
