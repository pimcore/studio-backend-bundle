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
namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Repository;

use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\Thumbnail;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailCollection;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\Asset\Video\Thumbnail\Config\Listing as VideoThumbnailListing;
use Pimcore\Model\Asset\Image\Thumbnail\Config\Listing as ImageThumbnailListing;

/**
 * @internal
 */
final class ThumbnailRepository implements ThumbnailRepositoryInterface
{
    private const DEFAULT_VIDEO_THUMBNAIL_ID = 'pimcore_system_treepreview';

    private const DEFAULT_IMAGE_THUMBNAIL_TEXT = 'original';

    public function listVideoThumbnails(
    ): ThumbnailCollection
    {

        $thumbnailListing = new VideoThumbnailListing();
        $thumbnails = $thumbnailListing->getThumbnails();

        return $this->getThumbnailCollection(
            $thumbnails,
            [
                new Thumbnail(
                    self::DEFAULT_VIDEO_THUMBNAIL_ID,
                    self::DEFAULT_IMAGE_THUMBNAIL_TEXT
                )
            ]
        );
    }

    public function listImageThumbnails(
    ): ThumbnailCollection
    {
        $thumbnailListing = new ImageThumbnailListing();
        $thumbnailListing->setFilter(function (Config $config) {
            return $config->isDownloadable();
        });
        $thumbnails = $thumbnailListing->getThumbnails();

        return $this->getThumbnailCollection(
            $thumbnails
        );
    }

    private function getThumbnailCollection(
        array $thumbnails,
        array $items = []
    ): ThumbnailCollection
    {
        foreach ($thumbnails as $thumbnail) {
            $items[] = new Thumbnail(
                $thumbnail->getName(),
                $thumbnail->getName()
            );
        }

        return new ThumbnailCollection(
            $items
        );
    }

}
