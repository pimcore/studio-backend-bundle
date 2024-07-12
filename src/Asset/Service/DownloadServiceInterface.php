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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidAssetFormatTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ThumbnailResizingFailedException;
use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
interface DownloadServiceInterface
{
    /**
     * @throws InvalidElementTypeException|ElementStreamResourceNotFoundException
     */
    public function downloadAsset(
        Asset $asset
    ): StreamedResponse;

    /**
     * @throws InvalidElementTypeException|ThumbnailResizingFailedException
     */
    public function downloadCustomImage(
        Asset $image,
        ImageDownloadConfigParameter $parameters
    ): BinaryFileResponse;

    /**
     * @throws InvalidElementTypeException|InvalidAssetFormatTypeException|ThumbnailResizingFailedException
     */
    public function downloadImageByFormat(
        Asset $image,
        string $format
    ): BinaryFileResponse;

    /**
     * @throws InvalidElementTypeException
     */
    public function downloadImageByThumbnail(
        Asset $image,
        string $thumbnailName
    ): BinaryFileResponse;

    /**
     * @throws NotFoundException|ForbiddenException|StreamResourceNotFoundException
     */
    public function downloadResourceByJobRunId(
        int $jobRunId,
        string $tempFileName,
        string $mimeType,
        string $downloadName,
    ): StreamedResponse;
}
