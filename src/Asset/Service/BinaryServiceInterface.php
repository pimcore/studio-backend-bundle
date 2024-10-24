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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementProcessingNotCompletedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailException;
use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
interface BinaryServiceInterface
{
    /**
     * @throws ElementProcessingNotCompletedException
     * @throws InvalidElementTypeException
     * @throws InvalidThumbnailException
     * @throws FilesystemException
     */
    public function downloadVideoByThumbnail(
        Asset $video,
        string $thumbnailName
    ): StreamedResponse;

    /**
     * @throws InvalidElementTypeException|InvalidThumbnailException
     */
    public function streamPreviewImageThumbnail(Asset $image): StreamedResponse;

    /**
     * @throws ElementProcessingNotCompletedException
     * @throws InvalidElementTypeException
     * @throws InvalidThumbnailException
     * @throws FilesystemException
     */
    public function streamVideoByThumbnail(
        Asset $video,
        string $thumbnailName
    ): StreamedResponse;

    /**
     * @throws ElementStreamResourceNotFoundException
     * @throws InvalidElementTypeException
     * @throws InvalidThumbnailConfigurationException
     * @throws InvalidThumbnailException
     */
    public function streamVideoImageThumbnail(
        Asset $video,
        VideoImageStreamConfigParameter $imageConfig
    ): StreamedResponse;
}
