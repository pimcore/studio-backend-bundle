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

namespace Pimcore\Bundle\StudioApiBundle\Response;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */

#[Schema(
    title: 'Collection',
    type: 'object'
)]
final readonly class Collection
{
    public function __construct(
        #[Property(description: 'total items', type: 'integer', example: 666)]
        private int $totalItems,
        #[Property(description: 'items', type: 'mixed', example: ['Asset', 'Folder', 'Document', 'DataObject'])]
        private array $items
    )
    {
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}