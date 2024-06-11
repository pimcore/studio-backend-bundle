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

use Pimcore\Bundle\StudioBackendBundle\Exception\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseHeaders;
use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
trait StreamedResponseTrait
{
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

        $headers = array_merge($additionalHeaders, [
            HttpResponseHeaders::HEADER_CONTENT_TYPE->value => $element->getMimeType(),
            HttpResponseHeaders::HEADER_CONTENT_DISPOSITION->value => sprintf(
                '%s; filename="%s"',
                $contentDisposition,
                $element->getFilename()
            ),
            HttpResponseHeaders::HEADER_CONTENT_LENGTH->value => $element->getFileSize(),
        ]);

        return new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
        }, 200, $headers);
    }
}
