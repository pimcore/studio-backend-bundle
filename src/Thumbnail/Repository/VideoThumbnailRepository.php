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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Repository;

use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Event\ThumbnailEvent;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\Thumbnail;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailCollection;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Thumbnails;
use Pimcore\Model\Asset\Video\Thumbnail\Config;
use Pimcore\Model\Asset\Video\Thumbnail\Config\Listing as VideoThumbnailListing;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class VideoThumbnailRepository implements VideoThumbnailRepositoryInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function listVideoThumbnails(): ThumbnailCollection
    {
        $thumbnailListing = new VideoThumbnailListing();
        $thumbnails = $thumbnailListing->getThumbnails();

        $items = [
            new Thumbnail(
                Thumbnails::DEFAULT_THUMBNAIL_ID->value,
                Thumbnails::DEFAULT_THUMBNAIL_TEXT->value
            ),
        ];

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

        return new ThumbnailCollection($items);
    }
}
