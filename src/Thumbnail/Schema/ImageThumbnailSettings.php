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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'ImageThumbnailSettings',
    title: 'Image Thumbnail Settings',
    required: [
        'name', 'description', 'group', 'format', 'quality', 'highResolution',
        'preserveColor', 'forceProcessICCProfiles', 'preserveMetaData', 'rasterizeSVG',
        'useCropBox', 'downloadable', 'modificationDate', 'creationDate', 'filenameSuffix', 'preserveAnimation',
    ],
    type: 'object'
)]
final readonly class ImageThumbnailSettings
{
    public function __construct(
        #[Property(description: 'Thumbnail name', type: 'string', example: 'portalCarousel')]
        private string $name,
        #[Property(description: 'Thumbnail description', type: 'string', example: '')]
        private ?string $description,
        #[Property(description: 'Thumbnail group', type: 'string', example: 'Areas')]
        private ?string $group,
        #[Property(description: 'Output format', type: 'string', example: 'SOURCE')]
        private string $format,
        #[Property(description: 'Quality setting', type: 'integer', example: 85)]
        private int $quality,
        #[Property(description: 'High resolution factor', type: 'number', example: 0, nullable: true)]
        private ?float $highResolution,
        #[Property(description: 'Preserve color profile', type: 'boolean', example: false)]
        private bool $preserveColor,
        #[Property(description: 'Force process ICC profiles', type: 'boolean', example: false)]
        private bool $forceProcessICCProfiles,
        #[Property(description: 'Preserve meta data', type: 'boolean', example: false)]
        private bool $preserveMetaData,
        #[Property(description: 'Rasterize SVG', type: 'boolean', example: false)]
        private bool $rasterizeSVG,
        #[Property(description: 'Use crop box', type: 'boolean', example: false)]
        private bool $useCropBox,
        #[Property(description: 'Is downloadable', type: 'boolean', example: true)]
        private bool $downloadable,
        #[Property(description: 'Modification date timestamp', type: 'integer', example: 1755073298, nullable: true)]
        private ?int $modificationDate,
        #[Property(description: 'Creation date timestamp', type: 'integer', example: 1565355190, nullable: true)]
        private ?int $creationDate,
        #[Property(description: 'Filename suffix', type: 'string', example: null, nullable: true)]
        private ?string $filenameSuffix,
        #[Property(description: 'Preserve animation', type: 'boolean', example: false)]
        private bool $preserveAnimation,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getQuality(): int
    {
        return $this->quality;
    }

    public function getHighResolution(): ?float
    {
        return $this->highResolution;
    }

    public function isPreserveColor(): bool
    {
        return $this->preserveColor;
    }

    public function isForceProcessICCProfiles(): bool
    {
        return $this->forceProcessICCProfiles;
    }

    public function isPreserveMetaData(): bool
    {
        return $this->preserveMetaData;
    }

    public function isRasterizeSVG(): bool
    {
        return $this->rasterizeSVG;
    }

    public function isUseCropBox(): bool
    {
        return $this->useCropBox;
    }

    public function isDownloadable(): bool
    {
        return $this->downloadable;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getFilenameSuffix(): ?string
    {
        return $this->filenameSuffix;
    }

    public function isPreserveAnimation(): bool
    {
        return $this->preserveAnimation;
    }
}
