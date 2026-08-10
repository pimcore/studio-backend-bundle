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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'RelatedElementData',
    title: 'RelatedElementData',
    required: ['id', 'type', 'subtype', 'fullPath', 'isPublished', 'hasAccess'],
    type: 'object'
)]
final readonly class RelatedElementData
{
    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 83)]
        private int $id,
        #[Property(description: 'Type of the element', type: 'string', example: 'object')]
        private string $type,
        #[Property(description: 'Subtype of the element', type: 'string', example: 'Product')]
        private string $subtype,
        #[Property(description: 'Full path of the element', type: 'string', example: '/path/to/element')]
        private string $fullPath,
        #[Property(description: 'Is the element published', type: 'boolean', example: true)]
        private ?bool $isPublished = null,
        #[Property(
            description: 'Whether the current user is allowed to view the element',
            type: 'boolean',
            example: true
        )]
        private bool $hasAccess = true,
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

    public function getSubtype(): string
    {
        return $this->subtype;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function getHasAccess(): bool
    {
        return $this->hasAccess;
    }
}
