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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'ThumbnailCollection',
    required: ['items'],
    type: 'object'
)]
final readonly class ThumbnailCollection
{
    public function __construct(
        #[Property(
            description: 'items',
            type: 'array',
            items: new Items(ref: Thumbnail::class)
        )]
        private array $items
    ) {
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
