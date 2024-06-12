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

#[Schema(
    title: 'Video',
    type: 'object'
)]
class Video extends Asset
{
    //use MetaData\EmbeddedMetaDataTrait;

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
        string $iconName,
        bool $hasChildren,
        string $type,
        string $filename,
        string $mimeType,
        bool $hasMetaData,
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
            $hasMetaData,
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
