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

namespace Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'BulkImportParameters',
    title: 'Bulk Import Parameters',
    required: ['items'],
    type: 'object'
)]
final readonly class BulkImportParameters
{
    public function __construct(
        #[Property(
            description: 'Items to import from the uploaded file',
            type: 'array',
            items: new Items(
                properties: [
                    new Property(property: 'type', description: 'Type of the item', type: 'string', example: 'class'),
                    new Property(property: 'name', description: 'Name of the item', type: 'string', example: 'Car'),
                ],
                type: 'object'
            )
        )]
        private array $items = [],
    ) {
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
