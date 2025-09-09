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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use League\Flysystem\FilesystemException;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\BasicStreamConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\DynamicConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ImageDownloadConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\VideoImageStreamConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementProcessingNotCompletedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StreamedResponseTrait;
use Pimcore\Messenger\AssetPreviewImageMessage;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Video;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
final readonly class BinaryService implements BinaryServiceInterface
{
    use StreamedResponseTrait;

    public function __construct(
        private ThumbnailServiceInterface $thumbnailService,
        private StorageServiceInterface $storageService,
        private MessageBusInterface $messageBus
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function downloadVideoByThumbnail(
        Asset $video,
        string $thumbnailName
    ): StreamedResponse {
        if (!$video instanceof Video) {
            throw new InvalidElementTypeException($video->getType(), ElementTypes::TYPE_ASSET);
        }

        return $this->getVideoByThumbnail($video, $thumbnailName, HttpResponseHeaders::ATTACHMENT_TYPE->value);
    }

    /**
     * {@inheritdoc}
     */
    public function streamImage(
        Asset $image
    ): StreamedResponse {
        if (!$image instanceof Image) {
            throw new InvalidElementTypeException($image->getType(), ElementTypes::TYPE_ASSET);
        }

        return $this->getStreamedResponse($image, HttpResponseHeaders::INLINE_TYPE->value);
    }

    /**
     * {@inheritdoc}
     */
    public function streamImageByThumbnail(
        ElementInterface $image,
        string $thumbnailName,
        ?BasicStreamConfigParameter $parameter = null
    ): StreamedResponse
    {
        if (!$image instanceof Image) {
            throw new InvalidElementTypeException($image->getType());
        }

        return $this->getStreamedResponse(
            $this->thumbnailService->getImageThumbnailByName($image, $thumbnailName, $parameter),
            HttpResponseHeaders::INLINE_TYPE->value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function streamPreviewImageThumbnail(Asset $image): StreamedResponse
    {
        if (!$image instanceof Image) {
            throw new InvalidElementTypeException($image->getType(), ElementTypes::TYPE_ASSET);
        }

        return $this->getStreamedResponse(
            $this->thumbnailService->getImagePreviewThumbnail($image),
            HttpResponseHeaders::INLINE_TYPE->value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function streamImageThumbnailFromConfig(
        Asset $image,
        ImageDownloadConfigParameter $configParameter
    ): StreamedResponse {
        if (!$image instanceof Image) {
            throw new InvalidElementTypeException($image->getType(), ElementTypes::TYPE_ASSET);
        }

        return $this->getStreamedResponse(
            $this->thumbnailService->getThumbnailFromConfiguration($image, $configParameter),
            HttpResponseHeaders::INLINE_TYPE->value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function streamDynamicImageThumbnail(
        Asset $image,
        DynamicConfigurationParameter $parameter
    ): StreamedResponse {
        if (!$image instanceof Image) {
            throw new InvalidElementTypeException($image->getType(), ElementTypes::TYPE_ASSET);
        }

        return $this->getStreamedResponse(
            $this->thumbnailService->getDynamicThumbnail($image, $parameter),
            HttpResponseHeaders::INLINE_TYPE->value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function streamVideoByThumbnail(
        Asset $video,
        string $thumbnailName
    ): StreamedResponse {
        if (!$video instanceof Video) {
            throw new InvalidElementTypeException($video->getType(), ElementTypes::TYPE_ASSET);
        }

        return $this->getVideoByThumbnail($video, $thumbnailName, HttpResponseHeaders::INLINE_TYPE->value);
    }

    /**
     * {@inheritdoc}
     */
    public function streamVideoImageThumbnail(
        Asset $video,
        VideoImageStreamConfigParameter $imageConfig
    ): StreamedResponse {
        if (!$video instanceof Video) {
            throw new InvalidElementTypeException($video->getType(), ElementTypes::TYPE_ASSET);
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
            $this->messageBus->dispatch(
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

        $storage = $this->storageService->getThumbnailStorage();
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
