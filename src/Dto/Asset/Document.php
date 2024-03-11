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

final class Document extends Asset
{
    //use MetaData\EmbeddedMetaDataTrait;
    private ?int $pageCount = null;

    private ?string $imageThumbnailPath = null;

    public function getPageCount(): ?int
    {
        return $this->pageCount;
    }

    public function setPageCount(?int $pageCount): void
    {
        $this->pageCount = $pageCount;
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
