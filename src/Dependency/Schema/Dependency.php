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

namespace Pimcore\Bundle\StudioBackendBundle\Dependency\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'Dependency',
    type: 'object'
)]
final readonly class Dependency
{
    public function __construct(
        #[Property(description: 'id', type: 'int')]
        private int $id,
        #[Property(description: 'path', type: 'string', example: 'text')]
        private string $path,
        #[Property(description: 'type', type: 'string', example: 'asset')]
        private string $type,
        #[Property(description: 'subType', type: 'string', example: 'image')]
        private string $subType,
        #[Property(description: 'published', type: 'bool', example: 'true')]
        private bool $published,
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