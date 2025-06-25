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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\Attribute\Response;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
final class ConvertedValueJson extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            required: ['data'],
            properties: [
                new Property(
                    'data',
                    title: 'data',
                    description: 'Converted value',
                    example: 2.0,
                    anyOf: [new Schema(type: 'number'), new Schema(type: 'integer')],
                ),
            ],
            type: 'object',
        );
    }
}
