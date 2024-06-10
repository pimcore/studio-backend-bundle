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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use League\Flysystem\FilesystemException;
use Pimcore\Bundle\StaticResolverBundle\Lib\VideoResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\Video\Thumbnail\ConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\VideoPreview;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidThumbnailException;
use Pimcore\Bundle\StudioBackendBundle\Exception\VideoPreviewNotAvaliableException;
use Pimcore\Model\Asset\Video;
use Pimcore\Tool\Storage;

/**
 * @internal
 */
final readonly class PreviewService implements PreviewServiceInterface
{
    public function __construct(
        private ConfigResolverInterface $configResolver,
        private DownloadServiceInterface $downloadService,
        private ThumbnailServiceInterface $thumbnailService,
        private VideoResolverInterface $videoResolver
    )
    {

    }

    /**
     * @throws InvalidThumbnailException|FilesystemException
     */
    public function getVideoPreview(
        Video $video,
        string $thumbnailName
    ): VideoPreview
    {
        $preview = null;
        $imageThumbnail = null;
        $videoAvailable = $this->videoResolver->isAvailable();
        $config = $this->thumbnailService->getVideoThumbnailConfig($thumbnailName);

        $thumbnail = $video->getThumbnail($config, ['mp4']);
        if (!$thumbnail) {
            throw new VideoPreviewNotAvaliableException();
        }

        if ($thumbnail['status'] === 'finished') {
            $storagePath = $video->getRealPath() . '/' .
                preg_replace(
                    '@^' . preg_quote($video->getPath(), '@') .
                    '@', '', urldecode($thumbnail['formats']['mp4'])
                );

            $storage = Storage::get('thumbnail');
            if (!$storage->fileExists($storagePath)) {
                throw new InvalidThumbnailException($thumbnailName);
            }
            $preview = $storage->readStream($storagePath);
        }
        

        return new VideoPreview(
            videoId: $video->getId(),
            videoAvailable: $videoAvailable,
            thumbnailName: $thumbnailName,
            preview: $preview,
            imageThumbnail: ''
        );
    }
}
