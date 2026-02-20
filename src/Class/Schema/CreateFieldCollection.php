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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'CreateFieldCollection',
    title: 'Schema used to create field collection definitions',
    required: ['key'],
    type: 'object'
)]
final readonly class CreateFieldCollection
{
    public function __construct(
        #[Property(description: 'Key of the field collection', type: 'string', example: 'MyFieldCollection')]
        private string $key,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
