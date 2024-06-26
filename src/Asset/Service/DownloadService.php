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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ImageDownloadConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ZipPathParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidAssetFormatTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ThumbnailResizingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\FormatTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\StreamedResponseTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
final readonly class DownloadService implements DownloadServiceInterface
{
    use StreamedResponseTrait;

    public function __construct(
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
     * @throws InvalidElementTypeException
     */
    public function downloadImageByThumbnail(
        ElementInterface $image,
        string $thumbnailName
    ): BinaryFileResponse {
        if (!$image instanceof Image) {
            throw new InvalidElementTypeException($image->getType());
        }

        try {
            $thumbnail = $image->getThumbnail($thumbnailName);
        } catch (Exception) {
            throw new InvalidThumbnailException($thumbnailName);
        }

        $thumbnailConfig = $thumbnail->getConfig();
        $autoFormatConfigs = $thumbnailConfig->getAutoFormatThumbnailConfigs();
        if ($autoFormatConfigs && $thumbnailConfig->getFormat() === strtoupper(FormatTypes::SOURCE)) {
            $thumbnail = $image->getThumbnail(current($autoFormatConfigs));
        }

        return $this->thumbnailService->getBinaryResponseFromThumbnail(
            $thumbnail,
            $image,
            false
        );
    }

    public function downloadZipArchiveByPath(ZipPathParameter $path): StreamedResponse
    {
        return $this->getZipStreamedResponse($path->getPath());
    }
}
