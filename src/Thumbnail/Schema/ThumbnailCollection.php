<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema;

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
            ref: Thumbnail::class,
            description: 'items',
            type: Thumbnail::class,
        )]
        private array $items
    ) {
    }

    public function getItems(): array
    {
        return $this->items;
    }
}