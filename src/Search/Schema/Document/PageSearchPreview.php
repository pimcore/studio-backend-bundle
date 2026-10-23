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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Schema\Document;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'SimpleSearchPageDetail',
    required: ['title', 'description', 'name', 'hasPreviewImage'],
    type: 'object'
)]
final readonly class PageSearchPreview
{
    public function __construct(
        #[Property(description: 'Title', type: 'string', example: 'Page')]
        private ?string $title,
        #[Property(description: 'Description', type: 'string', example: 'This is some page')]
        private ?string $description,
        #[Property(description: 'Navigation name', type: 'string', example: 'Awesome Page')]
        private ?string $name,
        #[Property(description: 'Has Preview image', type: 'bool', example: false)]
        private bool $hasPreviewImage = false,
    ) {
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function hasPreviewImage(): bool
    {
        return $this->hasPreviewImage;
    }
}
