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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Transformer',
    required: ['key'],
    type: 'object'
)]
final readonly class Transformer
{
    public function __construct(
        #[Property(description: 'Key of the Transformer', type: 'string', example: 'uppercase')]
        private string $key,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
