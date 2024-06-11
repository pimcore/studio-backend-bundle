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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Response\Header;

use OpenApi\Attributes\Header;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseHeaders;

/**
 * @internal
 */
final class ContentDisposition extends Header
{
    public function __construct(string $headerType = HttpResponseHeaders::ATTACHMENT_TYPE->value)
    {
        parent::__construct(
            header: HttpResponseHeaders::HEADER_CONTENT_DISPOSITION->value,
            description: 'Content-Disposition header',
            schema: new Schema(type: 'string', example: $headerType . '; filename="example.jpg"'),
        );
    }
}
