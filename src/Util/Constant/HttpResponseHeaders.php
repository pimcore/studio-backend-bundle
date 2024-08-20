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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant;

/**
 * @internal
 */
enum HttpResponseHeaders: string
{
    case HEADER_CONTENT_TYPE = 'Content-Type';
    case HEADER_CONTENT_DISPOSITION = 'Content-Disposition';
    case HEADER_CONTENT_LENGTH = 'Content-Length';
    case HEADER_ACCEPT_RANGES = 'Accept-Ranges';
    case ATTACHMENT_TYPE = 'attachment';
    case INLINE_TYPE = 'inline';
}
