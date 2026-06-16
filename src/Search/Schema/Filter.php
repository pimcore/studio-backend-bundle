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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'Search Filter',
    title: 'Search Filter',
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
        #[Property(description: 'Include Descendant Items', type: 'boolean', example: true)]
        private bool $includeDescendants,
        #[Property(description: 'Exclude Folders', type: 'boolean', example: true)]
        private bool $excludeFolders = true,
        #[Property(
            description: 'Column Filter',
            type: 'object',
            example: '[{"key":"name","type":"metadata.object","filterValue":1,"locale":"de"}]'
        )]
        private array $columnFilters = [],
        #[Property(
            description: 'Sort Filter',
            type: 'object',
            example: '{"key":"id","direction":"ASC","locale":"en"}'
        )]
        private array $sortFilter = [],
        #[Property(
            description: 'Additional Sort Filters for multi-column sorting',
            type: 'array',
            items: new Items(type: 'object'),
            example: '[{"key":"name","direction":"ASC"}]'
        )]
        private array $additionalSortFilters = [],
        #[Property(description: 'Path filter', type: 'string', example: '/root/cars', nullable: true)]
        private ?string $path = null,
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

    public function getExcludeFolders(): bool
    {
        return $this->excludeFolders;
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

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'pageSize' => $this->pageSize,
            'includeDescendants' => $this->includeDescendants,
            'excludeFolders' => $this->excludeFolders,
            'columnFilters' => $this->columnFilters,
            'sortFilter' => $this->sortFilter,
            'additionalSortFilters' => $this->additionalSortFilters,
            'path' => $this->path,
        ];
    }
}
