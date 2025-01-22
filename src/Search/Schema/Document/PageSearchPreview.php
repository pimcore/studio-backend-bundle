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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Schema\Document;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'SimpleSearchPageDetail',
    required: ['title', 'description', 'name', '$hasPreviewImage'],
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
