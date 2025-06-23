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

namespace Pimcore\Bundle\StudioBackendBundle\Dependency\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Dependency',
    required: ['id', 'path', 'type', 'subType', 'published'],
    type: 'object'
)]
final class Dependency implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'id', type: 'integer', example: 1020)]
        private readonly int $id,
        #[Property(description: 'path', type: 'string', example: 'text')]
        private readonly string $path,
        #[Property(description: 'type', type: 'string', example: 'asset')]
        private readonly string $type,
        #[Property(description: 'subType', type: 'string', example: 'image')]
        private readonly string $subType,
        #[Property(description: 'published', type: 'bool', example: 'true')]
        private readonly bool $published,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSubType(): string
    {
        return $this->subType;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }
}
