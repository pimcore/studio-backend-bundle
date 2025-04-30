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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header;

use OpenApi\Attributes\Header;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;

/**
 * @internal
 */
final class ContentDisposition extends Header
{
    public function __construct(
        string $headerType = HttpResponseHeaders::ATTACHMENT_TYPE->value,
        string $fileName = 'example.jpg'
    ) {
        parent::__construct(
            header: HttpResponseHeaders::HEADER_CONTENT_DISPOSITION->value,
            description: 'Content-Disposition header',
            schema: new Schema(type: 'string', example: $headerType . '; filename="' . $fileName . '"'),
        );
    }
}
