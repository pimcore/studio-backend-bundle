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

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ImageDownloadConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\VideoImageStreamConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ThumbnailResizingFailedException;
use Pimcore\Model\Asset\Document;
use Pimcore\Model\Asset\Document\ImageThumbnailInterface as DocumentThumbnail;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\ThumbnailInterface;
use Pimcore\Model\Asset\Video;
use Pimcore\Model\Asset\Video\ImageThumbnailInterface as VideoImageThumbnail;
use Pimcore\Model\Asset\Video\Thumbnail\Config as VideoThumbnailConfig;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @internal
 */
interface ThumbnailServiceInterface
{
    /**
     * @throws ThumbnailResizingFailedException
     */
    public function getThumbnailFromConfiguration(
        Image $image,
        ImageDownloadConfigParameter $parameters
    ): ThumbnailInterface;

    public function getBinaryResponseFromThumbnail(
        Image\ThumbnailInterface $thumbnail,
        Image $image,
        bool $deleteAfterSend = true
    ): BinaryFileResponse;

    /**
     * @throws InvalidThumbnailException
     */
    public function getImagePreviewThumbnail(Image $image): ThumbnailInterface;

    /**
     * @throws InvalidThumbnailException
     */
    public function getAssetImagePreviewThumbnail(Video|Document $asset): DocumentThumbnail|VideoImageThumbnail;

    /**
     * @throws InvalidThumbnailException
     */
    public function getVideoThumbnailConfig(
        string $thumbnailName
    ): VideoThumbnailConfig;

    /**
     * @throws InvalidThumbnailConfigurationException
     */
    public function validateCustomVideoThumbnailConfig(
        VideoImageStreamConfigParameter $imageConfig
    ): void;
}
