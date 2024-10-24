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
