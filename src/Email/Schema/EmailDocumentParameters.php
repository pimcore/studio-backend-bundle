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
        #[Property(description: 'parameter value', type: 'value', example: 'some_parameter_value')]
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
