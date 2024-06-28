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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Traits;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseHeaders;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Thumbnail\ImageThumbnailInterface;
use Pimcore\Model\Asset\Video;
use Pimcore\Model\Asset\Video\ImageThumbnailInterface as VideoImageThumbnailInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
trait StreamedResponseTrait
{
    /**
     * @throws ElementStreamResourceNotFoundException
     */
    protected function getStreamedResponse(
        Asset|VideoImageThumbnailInterface|ImageThumbnailInterface $element,
        string $contentDisposition = HttpResponseHeaders::ATTACHMENT_TYPE->value,
        array $additionalHeaders = [],
        ?int $fileSize = null,
    ): StreamedResponse {
        $stream = $element->getStream();

        if (!\is_resource($stream)) {
            throw new ElementStreamResourceNotFoundException(
                $element->getId(),
                $element->getType()
            );
        }

        if (!$fileSize) {
            $fileSize = $element->getFileSize();
        }

        return new StreamedResponse(
            function () use ($stream) {
                fpassthru($stream);
            },
            HttpResponseCodes::SUCCESS->value,
            $this->getResponseHeaders(
                mimeType: $element->getMimeType(),
                fileSize: $fileSize,
                filename: $element->getFilename(),
                contentDisposition: $contentDisposition,
                additionalHeaders: $additionalHeaders
            )
        );
    }

    /**
     * @throws FilesystemException
     */
    protected function getVideoStreamedResponse(
        Video $video,
        FilesystemOperator $storage,
        string $storagePath,
        string $contentDisposition = HttpResponseHeaders::ATTACHMENT_TYPE->value,
    ): StreamedResponse {
        $stream = $storage->readStream($storagePath);

        return new StreamedResponse(
            function () use ($stream) {
                fpassthru($stream);
            },
            HttpResponseCodes::SUCCESS->value,
            $this->getResponseHeaders(
                mimeType: 'video/mp4',
                fileSize: $storage->fileSize($storagePath),
                filename: $video->getFilename(),
                contentDisposition: $contentDisposition,
                additionalHeaders: [
                    HttpResponseHeaders::HEADER_ACCEPT_RANGES->value => 'bytes',
                ]
            )
        );
    }

    protected function getZipStreamedResponse(
        string $path
    ): StreamedResponse {
        $stream = fopen($path, 'rb');

        if (!$stream) {
            throw new StreamResourceNotFoundException(sprintf('Resource not found: %s', $path));
        }

        $response = new StreamedResponse(
            function () use ($stream) {
                fpassthru($stream);
            },
            HttpResponseCodes::SUCCESS->value,
            $this->getResponseHeaders(
                mimeType: 'application/zip',
                fileSize: filesize($path),
                filename: 'assets.zip',
                contentDisposition: HttpResponseHeaders::ATTACHMENT_TYPE->value
            ),

        );

        unlink($path);

        return $response;
    }

    private function getResponseHeaders(
        string $mimeType,
        int $fileSize,
        string $filename,
        string $contentDisposition,
        array $additionalHeaders = []
    ): array {
        return array_merge($additionalHeaders, [
            HttpResponseHeaders::HEADER_CONTENT_TYPE->value => $mimeType,
            HttpResponseHeaders::HEADER_CONTENT_DISPOSITION->value => sprintf(
                '%s; filename="%s"',
                $contentDisposition,
                $filename
            ),
            HttpResponseHeaders::HEADER_CONTENT_LENGTH->value => $fileSize,
        ]);
    }
}
