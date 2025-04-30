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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content;

use OpenApi\Attributes\MediaType as OpenApiMediaType;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
final class MediaType extends OpenApiMediaType
{
    public function __construct(string $mimeType = 'application/*')
    {
        parent::__construct(
            mediaType: $mimeType,
            schema: new Schema(
                type: 'string',
                format: 'binary'
            )
        );
    }
}
