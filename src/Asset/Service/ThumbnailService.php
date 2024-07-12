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
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\Video\Thumbnail\ConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ImageDownloadConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\VideoImageStreamConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ThumbnailResizingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\ResizeModes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ConsoleExecutableTrait;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\Thumbnail\Config as ImageThumbnailConfig;
use Pimcore\Model\Asset\Image\ThumbnailInterface;
use Pimcore\Model\Asset\Video\Thumbnail\Config as VideoThumbnailConfig;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
final readonly class ThumbnailService implements ThumbnailServiceInterface
{
    use ConsoleExecutableTrait;

    public function __construct(
        private ConfigResolverInterface $configResolver,
    ) {

    }

    /**
     * @throws ThumbnailResizingFailedException
     */
    public function getThumbnailFromConfiguration(
        Image $image,
        ImageDownloadConfigParameter $parameters
    ): ThumbnailInterface {
        $thumbnailConfig = $this->getImageThumbnailConfig($image, $parameters);
        $thumbnail = $image->getThumbnail($thumbnailConfig);
        $dpi = $parameters->getDpi();
        if ($dpi && $thumbnailConfig->getFormat() === MimeTypes::JPEG->value) {
            $this->resizeThumbnailFile($thumbnail, $dpi);
        }

        return $thumbnail;
    }

    public function getBinaryResponseFromThumbnail(
        Image\ThumbnailInterface $thumbnail,
        Image $image,
        bool $deleteAfterSend = true
    ): BinaryFileResponse {
        $downloadFilename = preg_replace(
            '/\.' . preg_quote(pathinfo($image->getFilename(), PATHINFO_EXTENSION), '/') . '$/i',
            '.' . $thumbnail->getFileExtension(),
            $image->getFilename()
        );

        clearstatcache();

        $response = new BinaryFileResponse($thumbnail->getLocalFile());
        $response->headers->set('Content-Type', $thumbnail->getMimeType());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $downloadFilename);
        $response->deleteFileAfterSend($deleteAfterSend);

        return $response;
    }

    /**
     * @throws InvalidThumbnailException
     */
    public function getVideoThumbnailConfig(
        string $thumbnailName
    ): VideoThumbnailConfig {
        try {
            $config = $this->configResolver->getByName($thumbnailName);
        } catch (Exception) {
            throw new InvalidThumbnailException($thumbnailName);
        }

        if (!$config instanceof VideoThumbnailConfig) {
            $config = $this->configResolver->getPreviewConfig();
        }

        return $config;
    }

    /**
     * @throws InvalidThumbnailConfigurationException
     */
    public function validateCustomVideoThumbnailConfig(
        VideoImageStreamConfigParameter $imageConfig
    ): void {
        if ($imageConfig->getFrame() && (!$imageConfig->getWidth() || !$imageConfig->getHeight())) {
            throw new InvalidThumbnailConfigurationException(
                'Width and height must be set for frame configuration'
            );
        }
        if ($imageConfig->getAspectRatio() && !$imageConfig->getWidth()) {
            throw new InvalidThumbnailConfigurationException(
                'Width must be set for aspect ratio configuration'
            );
        }
    }

    private function getImageThumbnailConfig(
        Image $image,
        ImageDownloadConfigParameter $parameters
    ): ImageThumbnailConfig {
        $thumbnailConfig = new ImageThumbnailConfig();
        $thumbnailConfig->setName('pimcore-download-' . $image->getId() . '-' . md5(serialize($parameters)));
        $thumbnailConfig = $this->setThumbnailConfigResizeParameters($parameters, $thumbnailConfig);
        $thumbnailConfig->setFormat($parameters->getMimeType());
        $quality = $parameters->getQuality();

        if ($quality !== null && $quality > 0 && $quality <= 100) {
            $thumbnailConfig->setQuality($quality);
        }

        $thumbnailConfig->setRasterizeSVG(true);
        if ($parameters->getMimeType() === MimeTypes::JPEG->value) {
            $thumbnailConfig->setPreserveMetaData(true);

            if ($quality === null) {
                $thumbnailConfig->setPreserveColor(true);
            }
        }

        return $thumbnailConfig;
    }

    private function setThumbnailConfigResizeParameters(
        ImageDownloadConfigParameter $parameters,
        ImageThumbnailConfig $thumbnailConfig
    ): ImageThumbnailConfig {
        $resizeWidth = $parameters->getWidth();
        $resizeHeight = $parameters->getHeight();

        match ($parameters->getResizeMode()) {
            ResizeModes::SCALE_BY_WIDTH => $thumbnailConfig->addItem(
                ResizeModes::SCALE_BY_WIDTH,
                [
                    'width' => $resizeWidth,
                ]
            ),
            ResizeModes::SCALE_BY_HEIGHT => $thumbnailConfig->addItem(
                ResizeModes::SCALE_BY_HEIGHT,
                [
                    'height' => $resizeHeight,
                ]
            ),
            default => $thumbnailConfig->addItem(
                ResizeModes::RESIZE,
                [
                    'width' => $resizeWidth,
                    'height' => $resizeHeight,
                ]
            ),
        };

        return $thumbnailConfig;
    }

    /**
     * @throws ThumbnailResizingFailedException
     */
    private function resizeThumbnailFile(
        ThumbnailInterface $thumbnail,
        int $dpi
    ): void {
        $exiftool = $this->getExecutable('exiftool', 'thumbnail resizing');

        try {
            $process = new Process([
                $exiftool, '-overwrite_original', '-xresolution=' . $dpi,
                '-yresolution=' . $dpi, '-resolutionunit=inches',
                $thumbnail->getLocalFile(),
            ]);
            $process->run();
        } catch (Exception $e) {
            throw new ThumbnailResizingFailedException($e->getMessage());
        }
    }
}
