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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentPermissions;
use Pimcore\Bundle\StudioBackendBundle\Document\Util\Trait\PageSnippetTrait;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

#[Schema(
    title: 'Email',
    required: [
        'subject', 'from', 'replyTo', 'to', 'cc', 'bcc',
        'controller', 'template', 'contentMainDocumentId', 'supportsContentMain',
        'missingRequiredEditable', 'staticGeneratorEnabled', 'staticGeneratorLifetime'
    ],
    type: 'object'
)]
final class Email extends Document
{
    use PageSnippetTrait;
    public function __construct(
        #[Property(description: 'Subject', type: 'string', example: 'Some subject')]
        private readonly string $subject,
        #[Property(description: 'From', type: 'string', example: 'some-sender@email')]
        private readonly string $from,
        #[Property(description: 'Reply to', type: 'string', example: 'some-reply@email')]
        private readonly string $replyTo,
        #[Property(description: 'To', type: 'string', example: 'some-receiver@email')]
        private readonly string $to,
        #[Property(description: 'CC', type: 'string', example: 'some-copy@email')]
        private readonly string $cc,
        #[Property(description: 'BCC', type: 'string', example: 'some-hidden-copy@email')]
        private readonly string $bcc,
        ?string $controller,
        ?string $template,
        ?int $contentMainDocumentId,
        bool $supportsContentMain,
        bool $missingRequiredEditable,
        bool $staticGeneratorEnabled,
        ?int $staticGeneratorLifetime,
        string $fullPath,
        bool $published,
        string $type,
        string $key,
        bool $hasChildren,
        bool $hasWorkflowWithPermissions,
        DocumentPermissions $permissions,
        int $id,
        int $parentId,
        string $path,
        ElementIcon $icon,
        int $userOwner,
        ?int $userModification,
        ?string $locked,
        bool $isLocked,
        ?int $creationDate,
        ?int $modificationDate,
    ) {
        $this->setController($controller);
        $this->setTemplate($template);
        $this->setContentMainDocumentId($contentMainDocumentId);
        $this->setSupportsContentMain($supportsContentMain);
        $this->setMissingRequiredEditable($missingRequiredEditable);
        $this->setStaticGeneratorEnabled($staticGeneratorEnabled);
        $this->setStaticGeneratorLifetime($staticGeneratorLifetime);

        parent::__construct(
            $fullPath,
            $published,
            $type,
            $key,
            $hasChildren,
            $hasWorkflowWithPermissions,
            $permissions,
            $id,
            $parentId,
            $path,
            $icon,
            $userOwner,
            $userModification,
            $locked,
            $isLocked,
            $creationDate,
            $modificationDate
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
