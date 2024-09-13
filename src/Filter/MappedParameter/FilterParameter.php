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

namespace Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ExcludeFolderParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\PathParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilterParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\TagFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\TagFilterParameterInterface;

/**
 * @internal
 */
final class FilterParameter implements
    CollectionParametersInterface,
    ExcludeFolderParameterInterface,
    PathParameterInterface,
    ColumnFiltersParameterInterface,
    SortFilterParameterInterface,
    TagFilterParameterInterface
{
    private ?string $path = null;

    public function __construct(
        private readonly int $page = 1,
        private readonly int $pageSize = 50,
        private readonly bool $includeDescendants = true,
        private readonly array $columnFilters = [],
        private readonly SortFilter $sortFilter = new SortFilter()
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

    public function getExcludeFolders(): bool
    {
        return true;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getPathIncludeParent(): bool
    {
        return false;
    }

    public function getPathIncludeDescendants(): bool
    {
        return $this->includeDescendants;
    }

    /**
     * @return ColumnFilter[]
     */
    public function getColumnFilterByType(string $type): iterable
    {
        $columns  = array_filter($this->columnFilters, static fn ($columnFilter) => $columnFilter['type'] === $type);

        foreach ($columns as $column) {
            if (!isset($column['key'], $column['type'], $column['filterValue'])) {
                throw new InvalidArgumentException('Invalid column filter');
            }

            yield new ColumnFilter(
                $column['key'],
                $column['type'],
                $column['filterValue']
            );
        }
    }

    public function getTagFilter(): ?TagFilterParameter
    {
        $columns  = array_filter(
            $this->columnFilters,
            static fn ($columnFilter) => $columnFilter['type'] === ColumnType::SYSTEM_TAG->value
        );

        if (count($columns) > 1) {
            throw new InvalidArgumentException('More than one tag filter is not allowed');
        }

        if (isset($columns[0]['filterValue'])) {
            $filterValue = $columns[0]['filterValue'];
            if (!isset($filterValue['tags'], $filterValue['considerChildTags'])) {
                throw new InvalidArgumentException('Invalid tag filter');
            }

            return new TagFilterParameter($filterValue['tags'], $filterValue['considerChildTags']);
        }

        return null;
    }


    public function getFirstColumnFilterByType(string $type): ?ColumnFilter
    {
        $columns = iterator_to_array($this->getColumnFilterByType($type));
        $column = reset($columns);

        return $column ?: null;
    }

    public function getSortFilter(): SortFilter
    {
        return $this->sortFilter;
    }
}
