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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant;

/**
 * @internal
 */
enum HttpResponseHeaders: string
{
    case HEADER_CONTENT_TYPE = 'Content-Type';
    case HEADER_CONTENT_DISPOSITION = 'Content-Disposition';
    case HEADER_CONTENT_LENGTH = 'Content-Length';
    case HEADER_CONTENT_ENCODING = 'Content-Encoding';
    case HEADER_ACCEPT_RANGES = 'Accept-Ranges';
    case HEADER_CACHE_CONTROL = 'Cache-Control';
    case ATTACHMENT_TYPE = 'attachment';
    case INLINE_TYPE = 'inline';
}
