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

use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Event\ThumbnailEvent;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\Thumbnail;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailCollection;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Thumbnails;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\Asset\Image\Thumbnail\Config\Listing as ImageThumbnailListing;
use Pimcore\Model\Asset\Video\Thumbnail\Config\Listing as VideoThumbnailListing;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class ThumbnailRepository implements ThumbnailRepositoryInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }
    
    public function listVideoThumbnails(
    ): ThumbnailCollection {
        $thumbnailListing = new VideoThumbnailListing();
        $thumbnails = $thumbnailListing->getThumbnails();

        return $this->getThumbnailCollection(
            $thumbnails,
            [
                new Thumbnail(
                    Thumbnails::DEFAULT_THUMBNAIL_ID->value,
                    Thumbnails::DEFAULT_THUMBNAIL_TEXT->value
                ),
            ]
        );
    }

    public function listImageThumbnails(
    ): ThumbnailCollection {
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
    ): ThumbnailCollection {
        /** @var Config $thumbnailConfig */
        foreach ($thumbnails as $thumbnailConfig) {
             $thumbnail = new Thumbnail(
                 $thumbnailConfig->getName(),
                 $thumbnailConfig->getName()
             );

             $this->eventDispatcher->dispatch(
                 new ThumbnailEvent($thumbnail),
                 ThumbnailEvent::EVENT_NAME
             );

             $items[] = $thumbnail;
        }

        return new ThumbnailCollection(
            $items
        );
    }
}
