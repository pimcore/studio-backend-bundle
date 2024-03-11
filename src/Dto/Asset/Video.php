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

namespace Pimcore\Bundle\StudioApiBundle\Dto\Asset;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset;

final class Video extends Asset
{
    //use MetaData\EmbeddedMetaDataTrait;
    private ?float $duration;

    private ?int $width;

    private ?int $height;

    private ?string $imageThumbnailPath;

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function setDuration(?float $duration): void
    {
        $this->duration = $duration;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): void
    {
        $this->width = $width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): void
    {
        $this->height = $height;
    }

    public function getImageThumbnailPath(): ?string
    {
        return $this->imageThumbnailPath;
    }

    public function setImageThumbnailPath(?string $imageThumbnailPath): void
    {
        $this->imageThumbnailPath = $imageThumbnailPath;
    }
}
