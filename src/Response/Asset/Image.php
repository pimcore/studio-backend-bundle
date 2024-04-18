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

namespace Pimcore\Bundle\StudioApiBundle\Response\Asset;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioApiBundle\Response\Asset;

#[Schema(
    title: 'Image',
    type: 'object'
)]
final class Image extends Asset
{
    public function __construct(
        #[Property(description: 'Format', type: 'string', example: 'muhFormat')]
        private readonly string $format,
        #[Property(description: 'width', type: 'integer', example: 666)]
        private readonly int $width,
        #[Property(description: 'height', type: 'integer', example: 333)]
        private readonly int $height,
        #[Property(description: 'is vector graphic', type: 'boolean', example: false)]
        private readonly bool $isVectorGraphic,
        #[Property(description: 'is animated', type: 'boolean', example: false)]
        private readonly bool $isAnimated,
        #[Property(description: 'path to thumbnail', type: 'string', example: '/path/to/element/hulk-smash.jpg')]
        private readonly string $thumbnailPath,
        string $iconName,
        bool $hasChildren,
        string $type,
        string $filename,
        string $mimeType,
        array $metaData,
        bool $workflowWithPermissions,
        string $fullPath,
        int $id,
        int $parentId,
        string $path,
        int $userOwner,
        int $userModification,
        ?string $locked,
        bool $isLocked,
        ?int $creationDate,
        ?int $modificationDate,
        Permissions $permissions
    ) {
        parent::__construct(
            $iconName,
            $hasChildren,
            $type,
            $filename,
            $mimeType,
            $metaData,
            $workflowWithPermissions,
            $fullPath,
            $id,
            $parentId,
            $path,
            $userOwner,
            $userModification,
            $locked,
            $isLocked,
            $creationDate,
            $modificationDate,
            $permissions
        );
    }

    public function getThumbnailPath(): string
    {
        return $this->thumbnailPath;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getIsVectorGraphic(): bool
    {
        return $this->isVectorGraphic;
    }

    public function getIsAnimated(): bool
    {
        return $this->isAnimated;
    }
}
