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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request;

use Attribute;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class MultipartFormDataRequestBody extends RequestBody
{
    public function __construct(array $properties, array $required = [])
    {
        parent::__construct(
            required: true,
            content: new MediaType(
                mediaType: 'multipart/form-data',
                schema: new Schema(
                    required: $required,
                    properties: $properties,
                    type: 'object',
                )
            ),
        );
    }
}
