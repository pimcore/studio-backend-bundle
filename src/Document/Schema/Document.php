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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\Element;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\CustomAttributesTrait;

#[Schema(
    schema: 'Document',
    title: 'Document',
    required: [
        'fullPath',
        'published',
        'type',
        'key',
        'index',
        'hasChildren',
        'hasWorkflowWithPermissions',
        'permissions',
        'documentDetailData',
        'isSite',
    ],
    type: 'object'
)]
class Document extends Element implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;
    use CustomAttributesTrait;

    public function __construct(
        #[Property(description: 'Full path', type: 'string', example: '/path/to/document')]
        private readonly string $fullPath,
        #[Property(description: 'Published', type: 'bool', example: false)]
        private readonly bool $published,
        #[Property(description: 'Type', type: 'string', example: 'link')]
        private readonly string $type,
        #[Property(description: 'Key', type: 'string', example: 'page.html')]
        private readonly string $key,
        #[Property(description: 'Custom index', type: 'integer', example: 0)]
        private readonly int $index,
        #[Property(description: 'Has children', type: 'bool', example: false)]
        private readonly bool $hasChildren,
        #[Property(description: 'Workflow permissions', type: 'bool', example: false)]
        private readonly bool $hasWorkflowWithPermissions,
        #[Property(ref: DocumentPermissions::class)]
        private readonly DocumentPermissions $permissions,
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
        #[Property(description: 'Is document a site', type: 'bool', example: false)]
        private readonly bool $isSite = false
    ) {
        parent::__construct(
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

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getHasChildren(): bool
    {
        return $this->hasChildren;
    }

    public function getHasWorkflowWithPermissions(): bool
    {
        return $this->hasWorkflowWithPermissions;
    }

    public function getPermissions(): DocumentPermissions
    {
        return $this->permissions;
    }

    public function getIsSite(): bool
    {
        return $this->isSite;
    }
}
