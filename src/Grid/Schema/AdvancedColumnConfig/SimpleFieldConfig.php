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
    title: 'Simple Field Config',
    required: ['field'],
    type: 'object'
)]
final readonly class SimpleFieldConfig
{
    public function __construct(
        #[Property(description: 'Field getter', type: 'string', example: 'name')]
        private string $field,
    ) {
    }

    public function getField(): string
    {
        return $this->field;
    }
}
