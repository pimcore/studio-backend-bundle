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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ThumbnailResizingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
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
     * @throws ElementStreamResourceNotFoundException|InvalidElementTypeException|ThumbnailResizingFailedException
     */
    public function getCustomDocumentThumbnail(
        Asset $document,
        DocumentImageDownloadConfigParameter $parameters,
        string $attachmentType = HttpResponseHeaders::ATTACHMENT_TYPE->value
    ): StreamedResponse;

    /**
     * @throws ElementStreamResourceNotFoundException|InvalidElementTypeException
     */
    public function streamDynamicDocumentThumbnail(
        Asset $document,
        DynamicConfigurationParameter $parameters
    ): StreamedResponse;

    /**
     * @throws ElementStreamResourceNotFoundException|InvalidElementTypeException|InvalidThumbnailException
     */
    public function getDocumentThumbnailByName(
        Asset $document,
        string $thumbnailName,
        int $page
    ): StreamedResponse;

    /**
     * @throws ElementStreamResourceNotFoundException|InvalidElementTypeException|InvalidThumbnailException
     */
    public function streamDocumentThumbnailByName(
        Asset $document,
        string $thumbnailName,
        ?DocumentStreamConfigParameter $parameter = null
    ): StreamedResponse;

    /**
     * @throws InvalidElementTypeException|InvalidAssetFormatTypeException|ThumbnailResizingFailedException
     */
    public function downloadImageByFormat(
        Asset $image,
        string $format
    ): BinaryFileResponse;

    /**
     * @throws InvalidElementTypeException|InvalidThumbnailException
     */
    public function downloadImageByThumbnail(
        Asset $image,
        string $thumbnailName
    ): BinaryFileResponse;
}
