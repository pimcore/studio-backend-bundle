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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\AssetPermissions;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

#[Schema(
    title: 'Video',
    type: 'object'
)]
class Video extends Asset
{
    public function __construct(
        #[Property(description: 'Duration', type: 'float', example: 43560.5)]
        private readonly ?float $duration,
        #[Property(description: 'Width', type: 'integer', example: 1920)]
        private readonly ?int $width,
        #[Property(description: 'Height', type: 'integer', example: 1080)]
        private readonly ?int $height,
        #[Property(
            description: 'Path to Image Thumbnail',
            type: 'string',
            example: '/path/to/video/imagethumbnail.jpg'
        )]
        private readonly?string $imageThumbnailPath,
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
        int $userModification,
        ?string $locked,
        bool $isLocked,
        ?int $creationDate,
        ?int $modificationDate,
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
        );
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getImageThumbnailPath(): ?string
    {
        return $this->imageThumbnailPath;
    }
}
