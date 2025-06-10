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
    title: 'Page',
    required: [
        'title', 'description', 'prettyUrl', 'controller', 'template', 'contentMainDocumentId', 'supportsContentMain',
        'missingRequiredEditable', 'staticGeneratorEnabled', 'staticGeneratorLifetime', 'draftData',
    ],
    type: 'object'
)]
final class Page extends Document
{
    use PageSnippetTrait;

    public function __construct(
        #[Property(description: 'Title', type: 'string', example: 'Link Title')]
        private readonly ?string $title,
        #[Property(description: 'Description', type: 'string', example: 'Link Description')]
        private readonly ?string $description,
        #[Property(description: 'Pretty Url', type: 'string', example: 'pretty/url')]
        private readonly ?string $prettyUrl,
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
        int $index,
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
            $index,
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrettyUrl(): ?string
    {
        return $this->prettyUrl;
    }
}
