<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Dto\Asset;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset;

class Image extends Asset
{
    //use MetaData\EmbeddedMetaDataTrait;

    public function __construct(
        private readonly string $format,
        private readonly int $width,
        private readonly int $height,
        private readonly bool $vectorGraphic,
        private readonly bool $animated,
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

    public function isVectorGraphic(): bool
    {
        return $this->vectorGraphic;
    }

    public function isAnimated(): bool
    {
        return $this->animated;
    }
    //
    //    public function getLowQualityPreviewPath(): string
    //    {
    //        return $this->asset->getLowQualityPreviewPath();
    //    }
    //
    //    public function getLowQualityPreviewDataUri(): ?string
    //    {
    //        return $this->asset->getLowQualityPreviewDataUri();
    //    }
}
