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
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ImageDownloadConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidAssetFormatTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidThumbnailException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ThumbnailResizingFailedException;
use Pimcore\Model\Element\ElementInterface;
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
        ElementInterface $asset
    ): StreamedResponse;

    /**
     * @throws InvalidElementTypeException|ThumbnailResizingFailedException
     */
    public function downloadCustomImage(
        ElementInterface $image,
        ImageDownloadConfigParameter $parameters
    ): BinaryFileResponse;

    /**
     * @throws InvalidElementTypeException|InvalidAssetFormatTypeException|ThumbnailResizingFailedException
     */
    public function downloadImageByFormat(
        ElementInterface $image,
        string $format
    ): BinaryFileResponse;

    /**
     * @throws InvalidElementTypeException
     */
    public function downloadImageByThumbnail(
        ElementInterface $image,
        string $thumbnailName
    ): BinaryFileResponse;

    /**
     * @throws InvalidElementTypeException|InvalidThumbnailException|FilesystemException
     */
    public function downloadVideoByThumbnail(
        ElementInterface $video,
        string $thumbnailName,
        string $headerType = 'attachment'
    ): StreamedResponse;
}
