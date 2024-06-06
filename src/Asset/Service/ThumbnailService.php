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
use Pimcore\Bundle\StudioBackendBundle\Exception\ThumbnailResizingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\ResizeModes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ConsoleExecutableTrait;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\ThumbnailInterface;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
final readonly class ThumbnailService implements ThumbnailServiceInterface
{
    use ConsoleExecutableTrait;

    public function getThumbnailFromConfiguration(
        Image $image,
        ImageDownloadConfigParameter $parameters
    ): ThumbnailInterface
    {
        $thumbnailConfig = $this->getThumbnailConfig($image, $parameters);
        $thumbnail = $image->getThumbnail($thumbnailConfig);
        $dpi = $parameters->getDpi();
        if ($dpi && $thumbnailConfig->getFormat() === MimeTypes::JPEG) {
            $this->resizeThumbnailFile($thumbnail, $dpi);
        }

        return $thumbnail;
    }

    public function getBinaryResponseFromThumbnail(
        Image\ThumbnailInterface $thumbnail,
        Image $image,
        bool $deleteAfterSend = true
    ): BinaryFileResponse
    {
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

    private function getThumbnailConfig(
        Image $image,
        ImageDownloadConfigParameter $parameters
    ): Config
    {
        $thumbnailConfig = new Config();
        $thumbnailConfig->setName('pimcore-download-' . $image->getId() . '-' . md5(serialize($parameters)));
        $thumbnailConfig = $this->setThumbnailConfigResizeParameters($parameters, $thumbnailConfig);
        $thumbnailConfig->setFormat($parameters->getMimeType());
        $quality = $parameters->getQuality();

        if ($quality !== null && $quality > 0 && $quality <= 100 ) {
            $thumbnailConfig->setQuality($quality);
        }

        $thumbnailConfig->setRasterizeSVG(true);
        if ($parameters->getMimeType() === MimeTypes::JPEG) {
            $thumbnailConfig->setPreserveMetaData(true);

            if ($quality === null) {
                $thumbnailConfig->setPreserveColor(true);
            }
        }

        return $thumbnailConfig;
    }

    private function setThumbnailConfigResizeParameters(
        ImageDownloadConfigParameter $parameters,
        Config $thumbnailConfig
    ): Config
    {
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

    private function resizeThumbnailFile(
        ThumbnailInterface $thumbnail,
        int $dpi
    ): void
    {
        $exiftool = $this->getExecutable('exiftool', 'thumbnail resizing');
        try {
            $process = new Process([
                $exiftool, '-overwrite_original', '-xresolution=' . $dpi,
                '-yresolution=' . $dpi, '-resolutionunit=inches',
                $thumbnail->getLocalFile()
            ]);
            $process->run();
        } catch (Exception $e) {
            throw new ThumbnailResizingFailedException($e->getMessage());
        }
    }
}