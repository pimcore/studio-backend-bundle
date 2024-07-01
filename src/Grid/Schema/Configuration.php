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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'GridConfiguration',
    type: 'object'
)]
final readonly class Configuration
{
    /**
     * @param array<int, ColumnDefinition> $columns
     */
    public function __construct(
        #[Property(
            property: 'columns',
            type: 'array',
            items: new Items(ref: ColumnDefinition::class)
        )]
        private array $columns,
    ) {
    }

    /**
     * @return array<int, ColumnDefinition>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}