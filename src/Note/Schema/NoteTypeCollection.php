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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'NoteTypeCollection',
    required: ['items'],
    type: 'object'
)]
final readonly class NoteTypeCollection
{
    public function __construct(
        #[Property(
            description: 'items',
            type: 'array',
            items: new Items(ref: NoteType::class)
        )]
        private array $items
    ) {
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
