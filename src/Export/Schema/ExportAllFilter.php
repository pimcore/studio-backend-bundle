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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * Contains all data that is needed to get all the data for the column.
 *
 * @internal
 */
#[Schema(
    schema: 'ExportAllFilter',
    title: 'Export All Filter',
    required: ['columnFilters', 'sortFilter'],
    type: 'object'
)]
final readonly class ExportAllFilter
{
    public function __construct(
        #[Property(
            description: 'Column Filter',
            type: 'object',
            example: '[{"key":"name","type": "metadata.object","filterValue": 1, "locale":"de"}]'
        )]
        private array $columnFilters = [],
        #[Property(
            description: 'Sort Filter',
            type: 'object',
            example: '{"key":"id","direction": "ASC"}'
        )]
        private array $sortFilter = [],
        #[Property(
            description: 'Additional Sort Filters for multi-column sorting',
            type: 'array',
            items: new Items(type: 'object'),
            example: '[{"key":"name","direction": "ASC"}]'
        )]
        private array $additionalSortFilters = [],
    ) {
    }

    public function getColumnFilters(): array
    {
        return $this->columnFilters;
    }

    public function getSortFilter(): array
    {
        return $this->sortFilter;
    }

    public function getAdditionalSortFilters(): array
    {
        return $this->additionalSortFilters;
    }
}
