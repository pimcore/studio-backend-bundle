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
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseHeaders;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Video;
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
        Asset $element,
        string $contentDisposition = HttpResponseHeaders::ATTACHMENT_TYPE->value,
        array $additionalHeaders = []
    ): StreamedResponse {
        $stream = $element->getStream();

        if (!is_resource($stream)) {
            throw new ElementStreamResourceNotFoundException(
                $element->getId(),
                $element->getType()
            );
        }

        return new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
        }, 200, $this->getResponseHeaders(
            mimeType: $element->getMimeType(),
            fileSize: $element->getFileSize(),
            filename: $element->getFilename(),
            contentDisposition: $contentDisposition,
            additionalHeaders: $additionalHeaders
        ));
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

        return new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
        }, 200, $this->getResponseHeaders(
            mimeType: 'video/mp4',
            fileSize: $storage->fileSize($storagePath),
            filename: $video->getFilename(),
            contentDisposition: $contentDisposition,
            additionalHeaders: [
                HttpResponseHeaders::HEADER_ACCEPT_RANGES->value => 'bytes',
            ]
        ));
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
