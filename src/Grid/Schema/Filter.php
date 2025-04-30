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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * Contains all data that is needed to get all the data for the column.
 *
 * @internal
 */
#[Schema(
    title: 'Grid Filter',
    required: ['page', 'pageSize', 'includeDescendants'],
    type: 'object'
)]
final readonly class Filter
{
    public function __construct(
        #[Property(description: 'Page', type: 'integer', example: 1)]
        private int $page,
        #[Property(description: 'Page Size', type: 'integer', example: 50)]
        private int $pageSize,
        #[Property(description: 'Include Descendant Items', type: 'boolean', example: false)]
        private bool $includeDescendants,
        #[Property(
            description: 'Column Filter',
            type: 'object',
            example: '[{"key":"name","type": "metadata.object","filterValue": 1}]'
        )]
        private array $columnFilters = [],
        #[Property(
            description: 'Sort Filter',
            type: 'object',
            example: '{"key":"id","direction": "ASC"}'
        )]
        private array $sortFilter = []

    ) {
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getIncludeDescendants(): bool
    {
        return $this->includeDescendants;
    }

    public function getColumnFilters(): array
    {
        return $this->columnFilters;
    }

    public function getSortFilter(): array
    {
        return $this->sortFilter;
    }

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'pageSize' => $this->pageSize,
            'includeDescendants' => $this->includeDescendants,
            'columnFilters' => $this->columnFilters,
            'sortFilter' => $this->sortFilter,
        ];
    }
}
