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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'EmailDocumentParameters',
    required: ['key', 'value'],
    type: 'object'
)]
final readonly class EmailDocumentParameters
{
    public function __construct(
        #[Property(description: 'parameter key', type: 'string', example: 'some_parameter_key')]
        private string $key,
        #[Property(description: 'parameter value', example: 'some_parameter_value', anyOf: [
            new Schema(type: 'string'),
            new Schema(type: 'number'),
            new Schema(type: 'boolean'),
            new Schema(type: 'object'),
        ])]
        private mixed $value,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
