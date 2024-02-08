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

use Exception;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Model\Asset\Image as ModelImage;
use Pimcore\Model\Asset\Image\ThumbnailInterface;

class Image extends Asset
{
    public function __construct(private readonly ModelImage $asset, Permissions $permission)
    {
        parent::__construct($asset, $permission);
    }

    public function getLowQualityPreviewPath(): string
    {
        return $this->asset->getLowQualityPreviewPath();
    }

    public function getLowQualityPreviewDataUri(): ?string
    {
        return $this->asset->getLowQualityPreviewDataUri();
    }

    public function getThumbnail(
        array|string|ModelImage\Thumbnail\Config|null $config = null,
        bool $deferred = true
    ): ThumbnailInterface {
        return $this->asset->getThumbnail($config, $deferred);
    }

    public function getFormat(): string
    {
        return $this->asset->getFormat();
    }

    /**
     * @param string|null $path
     * @param bool $force
     * @return array|null
     * @throws Exception
     */
    public function getDimensions(string $path = null, bool $force = false): ?array
    {
        return $this->asset->getDimensions($path, $force);
    }

    public function getWidth(): int
    {
        return $this->asset->getWidth();
    }

    public function getHeight(): int
    {
        return $this->asset->getHeight();
    }

    public function isVectorGraphic(): bool
    {
        return $this->asset->isVectorGraphic();
    }

    public function isAnimated(): bool
    {
        return $this->asset->isAnimated();
    }
}
