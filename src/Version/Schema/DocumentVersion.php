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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'DocumentVersion',
    required: ['modificationDate', 'path', 'published'],
    type: 'object'
)]
final class DocumentVersion implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'modification date', type: 'integer', example: 1712823182)]
        private readonly int $modificationDate,
        #[Property(description: 'path', type: 'string', example: '/path/to/object')]
        private readonly string $path,
        #[Property(description: 'published', type: 'bool', example: true)]
        private readonly bool $published
    ) {

    }

    public function getModificationDate(): int
    {
        return $this->modificationDate;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }
}
