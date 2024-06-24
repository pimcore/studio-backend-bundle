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

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ImageDownloadConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\VideoImageStreamConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ThumbnailResizingFailedException;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\ThumbnailInterface;
use Pimcore\Model\Asset\Video\Thumbnail\Config as VideoThumbnailConfig;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
