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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet;



use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentPermissions;


#[Schema(
    schema: 'PageSnippet',
    title: 'PageSnippet',
    required: [
        'titel',
        'description',
    ],
    type: 'object'
)]
final class PageSnippet extends Document
{
    public function __construct(
        private string $fullPath,
        private bool $published,
        private string $type,
        private string $key,
        private int $index,
        private bool $hasChildren,
        private bool $hasWorkflowWithPermissions,
        private DocumentPermissions $permissions,
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
        private bool $isSite = false,
        private bool $navigationExclude = false,
        #[Property(description: 'Title of the Page Snippet', type: 'string', example: 'Title')]
        private ?string $title = null,
        #[Property(description: 'Description of the Page Snippet', type: 'string', example: 'Description')]
        private ?string $description = null,
    ) {
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
            $modificationDate,
            $isSite = false,
            $navigationExclude = false,
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
}
