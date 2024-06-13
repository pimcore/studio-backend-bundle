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
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\VideoImageStreamConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementProcessingNotCompletedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\StreamedResponseTrait;
use Pimcore\Messenger\AssetPreviewImageMessage;
use Pimcore\Model\Asset\Video;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tool\Storage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
final readonly class BinaryService implements BinaryServiceInterface
{
    use StreamedResponseTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private ThumbnailServiceInterface $thumbnailService,
        private Storage $storageTool
    )
    {
    }

    /**
     * @throws ElementProcessingNotCompletedException
     * @throws InvalidElementTypeException
     * @throws InvalidThumbnailException
     * @throws FilesystemException
     */
    public function downloadVideoByThumbnail(
        ElementInterface $video,
        string $thumbnailName
    ): StreamedResponse
    {
        if (!$video instanceof Video) {
            throw new InvalidElementTypeException($video->getType());
        }

        return $this->getVideoByThumbnail($video, $thumbnailName, HttpResponseHeaders::ATTACHMENT_TYPE->value);
    }

    /**
     * @throws ElementProcessingNotCompletedException
     * @throws InvalidElementTypeException
     * @throws InvalidThumbnailException
     * @throws FilesystemException
     */
    public function streamVideoByThumbnail(
        ElementInterface $video,
        string $thumbnailName
    ): StreamedResponse
    {
        if (!$video instanceof Video) {
            throw new InvalidElementTypeException($video->getType());
        }

        return $this->getVideoByThumbnail($video, $thumbnailName, HttpResponseHeaders::INLINE_TYPE->value);
    }

    /**
     * @throws ElementStreamResourceNotFoundException
     * @throws InvalidElementTypeException
     * @throws InvalidThumbnailConfigurationException
     * @throws InvalidThumbnailException
     */
    public function streamVideoImageThumbnail(
        ElementInterface $video,
        VideoImageStreamConfigParameter $imageConfig
    ): StreamedResponse
    {
        if (!$video instanceof Video) {
            throw new InvalidElementTypeException($video->getType());
        }
        $this->thumbnailService->validateCustomVideoThumbnailConfig($imageConfig);

        $imageParameters = [];
        if ($imageConfig->getWidth()) {
            $imageParameters['width'] = $imageConfig->getWidth();
        }
        if ($imageConfig->getHeight()) {
            $imageParameters['height'] = $imageConfig->getHeight();
        }
        if ($imageConfig->getAspectRatio()) {
            $imageParameters['aspectratio'] = $imageConfig->getAspectRatio();
        }
        if ($imageConfig->getFrame()) {
            $imageParameters['frame'] = $imageConfig->getFrame();
        }

        $image = $video->getImageThumbnail($imageParameters);

        if ($imageConfig->getAsync() && !$image->exists()) {
            $this->eventDispatcher->dispatch(
                new AssetPreviewImageMessage($video->getId())
            );

            throw new ElementStreamResourceNotFoundException(
                $video->getId(),
                'video image thumbnail for video'
            );
        }

        return $this->getStreamedResponse(
            $image,
            HttpResponseHeaders::INLINE_TYPE->value
        );
    }

    /**
     * @throws ElementProcessingNotCompletedException|FilesystemException|InvalidThumbnailException
     */
    private function getVideoByThumbnail(
        Video $video,
        string $thumbnailName,
        string $contentDisposition
    ): StreamedResponse {
        $configuration = $this->thumbnailService->getVideoThumbnailConfig($thumbnailName);
        $thumbnail = $video->getThumbnail($configuration, ['mp4']);
        if (!$thumbnail) {
            throw new InvalidThumbnailException($thumbnailName);
        }
        if (!isset($thumbnail['status']) || $thumbnail['status'] !== 'finished') {
            throw new ElementProcessingNotCompletedException($video->getId(), 'Thumbnail for video');
        }

        $storagePath = $video->getRealPath() . '/' .
            preg_replace(
                '@^' . preg_quote($video->getPath(), '@') . '@',
                '',
                urldecode($thumbnail['formats']['mp4'])
            );

        $storage = $this->storageTool->getStorage('thumbnail');
        if (!$storage->fileExists($storagePath)) {
            throw new InvalidThumbnailException($thumbnailName);
        }

        return $this->getVideoStreamedResponse(
            $video,
            $storage,
            $storagePath,
            $contentDisposition
        );
    }
}
