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

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\DocumentImageDownloadConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\DocumentStreamConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\DynamicConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ImageDownloadConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidAssetFormatTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ThumbnailResizingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\FormatTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StreamedResponseTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\TempFilePathTrait;
use Pimcore\Messenger\AssetPreviewImageMessage;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Document;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use function in_array;

/**
 * @internal
 */
final readonly class DownloadService implements DownloadServiceInterface
{
    use StreamedResponseTrait;
    use TempFilePathTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
        private ThumbnailServiceInterface $thumbnailService,
        private array $defaultFormats,
    ) {
    }

    /**
     * @throws InvalidElementTypeException|ElementStreamResourceNotFoundException
     */
    public function downloadAsset(
        Asset $asset
    ): StreamedResponse {
        return $this->getStreamedResponse($asset, HttpResponseHeaders::ATTACHMENT_TYPE->value);
    }

    /**
     * @throws InvalidElementTypeException|ThumbnailResizingFailedException
     */
    public function downloadCustomImage(
        Asset $image,
        ImageDownloadConfigParameter $parameters
    ): BinaryFileResponse {
        if (!$image instanceof Image) {
            throw new InvalidElementTypeException($image->getType());
        }

        return $this->thumbnailService->getBinaryResponseFromThumbnail(
            $this->thumbnailService->getThumbnailFromConfiguration($image, $parameters),
            $image
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomDocumentThumbnail(
        Asset $document,
        DocumentImageDownloadConfigParameter $parameters,
        string $attachmentType = HttpResponseHeaders::ATTACHMENT_TYPE->value
    ): StreamedResponse {
        if (!$document instanceof Document) {
            throw new InvalidElementTypeException($document->getType());
        }

        $thumbnailConfig = $this->thumbnailService->getDocumentThumbnailConfig($document, $parameters);

        return $this->getStreamedDocumentThumbnail(
            $document,
            $thumbnailConfig,
            $parameters->getPage() ?? 1,
            $attachmentType
        );
    }

    /**
     * {@inheritdoc}
     */
    public function streamDynamicDocumentThumbnail(
        Asset $document,
        DynamicConfigurationParameter $parameters
    ): StreamedResponse {
        if (!$document instanceof Document) {
            throw new InvalidElementTypeException($document->getType());
        }

        $thumbnailConfig = $this->thumbnailService->getDynamicDocumentThumbnail($document, $parameters);

        return $this->getStreamedDocumentThumbnail(
            $document,
            $thumbnailConfig,
            $parameters->getDynamicConfig()['page'] ?? 1,
            HttpResponseHeaders::INLINE_TYPE->value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentThumbnailByName(
        Asset $document,
        string $thumbnailName,
        int $page
    ): StreamedResponse {
        if (!$document instanceof Document) {
            throw new InvalidElementTypeException($document->getType());
        }

        $thumbnailConfig = $this->thumbnailService->getImageThumbnailConfigByName($thumbnailName);

        return $this->getStreamedDocumentThumbnail(
            $document,
            $thumbnailConfig,
            $page,
            HttpResponseHeaders::ATTACHMENT_TYPE->value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function streamDocumentThumbnailByName(
        Asset $document,
        string $thumbnailName,
        ?DocumentStreamConfigParameter $parameter = null
    ): StreamedResponse {
        if (!$document instanceof Document) {
            throw new InvalidElementTypeException($document->getType());
        }

        $thumbnailConfig = $this->thumbnailService->getImageThumbnailConfigByName($thumbnailName, $parameter);

        $page = $parameter?->getPage() ?? 1;

        return $this->getStreamedDocumentThumbnail(
            $document,
            $thumbnailConfig,
            $page,
            HttpResponseHeaders::INLINE_TYPE->value
        );
    }

    /**
     * @throws InvalidElementTypeException|InvalidAssetFormatTypeException|ThumbnailResizingFailedException
     */
    public function downloadImageByFormat(ElementInterface $image, string $format): BinaryFileResponse
    {
        if (!$image instanceof Image) {
            throw new InvalidElementTypeException($image->getType());
        }

        if (!in_array($format, FormatTypes::ALLOWED_FORMATS)) {
            throw new InvalidAssetFormatTypeException($format);
        }
        $configuration = $this->defaultFormats[$format];
        if (!$configuration) {
            throw new InvalidAssetFormatTypeException($format);
        }
        $parameters = new ImageDownloadConfigParameter(
            mimeType: $configuration['format'],
            resizeMode: $configuration['resize_mode'],
            width: $configuration['width'] ?? null,
            height: $configuration['height'] ?? null,
            quality: $configuration['quality'] ?? null,
            dpi: $configuration['dpi'] ?? null
        );

        return $this->thumbnailService->getBinaryResponseFromThumbnail(
            $this->thumbnailService->getThumbnailFromConfiguration($image, $parameters),
            $image
        );
    }

    /**
     * {@inheritdoc}
     */
    public function downloadImageByThumbnail(
        ElementInterface $image,
        string $thumbnailName
    ): BinaryFileResponse {
        if (!$image instanceof Image) {
            throw new InvalidElementTypeException($image->getType());
        }

        return $this->thumbnailService->getBinaryResponseFromThumbnail(
            $this->thumbnailService->getImageThumbnailByName($image, $thumbnailName),
            $image,
            false
        );
    }

    public function downloadJSON(
        string $json,
        string $filename
    ): JsonResponse {
        $response = new JsonResponse(
            $json
        );
        $response->headers->set(
            HttpResponseHeaders::HEADER_CONTENT_TYPE->value,
            MimeTypes::JSON->value
        );
        $response->headers->set(
            HttpResponseHeaders::HEADER_CONTENT_DISPOSITION->value,
            'attachment; filename="' . $filename .'"'
        );

        return $response;
    }

    /**
     * @throws ElementStreamResourceNotFoundException
     */
    private function getStreamedDocumentThumbnail(
        Document $document,
        Config $thumbnailConfig,
        int $page,
        string $attachmentType = HttpResponseHeaders::ATTACHMENT_TYPE->value
    ): StreamedResponse {
        $thumbnail = $document->getImageThumbnail($thumbnailConfig, $page);
        if (!$thumbnail->exists()) {
            $this->messageBus->dispatch(
                new AssetPreviewImageMessage($document->getId())
            );

            throw new ElementStreamResourceNotFoundException(
                $document->getId(),
                'image thumbnail for document'
            );
        }

        return $this->getStreamedResponse($thumbnail, $attachmentType);
    }
}
