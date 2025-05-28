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
    title: 'Existing Column Config',
    description: 'This config is used to get the date form an already configured advanced column.',
    required: ['text'],
    type: 'object'
)]
final readonly class ExistingColumnConfig
{
    public function __construct(
        #[Property(description: 'Name of the existing Column', type: 'string', example: 'my_column')]
        private string $existingColumnName,
    ) {
    }

    public function getExistingColumnName(): string
    {
        return $this->existingColumnName;
    }
}
