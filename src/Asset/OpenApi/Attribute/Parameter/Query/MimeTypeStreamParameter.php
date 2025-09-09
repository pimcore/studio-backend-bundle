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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;

#[Attribute(Attribute::TARGET_METHOD)]
final class MimeTypeStreamParameter extends QueryParameter
{
    public function __construct(string $defaultValue = MimeTypes::JPEG->value)
    {
        parent::__construct(
            name: 'mimeType',
            description: 'Mime type of steamed image.',
            in: 'query',
            required: false,
            schema: new Schema(
                type: 'string',
                enum: [
                    MimeTypes::JPEG->value,
                    MimeTypes::PNG->value,
                    MimeTypes::SOURCE->value,
                    MimeTypes::ORIGINAL->value,
                    MimeTypes::PRINT->value,
                ],
                example: $defaultValue
            ),
        );
    }
}
