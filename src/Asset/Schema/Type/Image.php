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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\AssetPermissions;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

#[Schema(
    title: 'Image',
    required: [
        'width',
        'height',
        'imageThumbnailPath',
    ],
    type: 'object'
)]
final class Image extends Asset implements ThumbnailPathInterface
{
    public function __construct(
        #[Property(description: 'width', type: 'integer', example: 666)]
        private readonly int $width,
        #[Property(description: 'height', type: 'integer', example: 333)]
        private readonly int $height,
        #[Property(description: 'path to thumbnail', type: 'string', example: '/path/to/element/hulk-smash.jpg')]
        private readonly string $imageThumbnailPath,
        bool $hasChildren,
        string $type,
        string $filename,
        string $mimeType,
        bool $hasMetadata,
        bool $workflowWithPermissions,
        string $fullPath,
        AssetPermissions $permissions,
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
        array $metadata = [],
        int $fileSize = 0,
    ) {
        parent::__construct(
            $hasChildren,
            $type,
            $filename,
            $mimeType,
            $hasMetadata,
            $workflowWithPermissions,
            $fullPath,
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
            $metadata,
            $fileSize
        );
    }

    public function getImageThumbnailPath(): string
    {
        return $this->imageThumbnailPath;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }
}
