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

namespace Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ClassIdParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ClassNameParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ExcludeFolderParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\PathParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFiltersParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilterParameterInterface;
use function array_key_exists;
use function count;

/**
 * @internal
 */
final class FilterParameter implements
    CollectionParametersInterface,
    ExcludeFolderParameterInterface,
    PathParameterInterface,
    ColumnFiltersParameterInterface,
    SimpleColumnFiltersParameterInterface,
    SortFilterParameterInterface,
    ClassNameParametersInterface,
    ClassIdParametersInterface
{
    private ?string $path = null;

    private ?string $className = null;

    private ?string $classId = null;

    private bool $excludeFolders = true;

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

    public function getStart(): int
    {
        $page = $this->page - 1;
        if ($page < 0) {
            $page = 0;
        }


        return $page * $this->pageSize;
    }

    public function getExcludeFolders(): bool
    {
        return $this->excludeFolders;
    }

    public function setExcludeFolders(bool $excludeFolders): void
    {
        $this->excludeFolders = $excludeFolders;
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
            if (!isset($column['key'], $column['type']) || !array_key_exists('filterValue', $column)) {
                throw new InvalidArgumentException('Invalid column filter');
            }

            yield new ColumnFilter(
                $column['key'],
                $column['type'],
                $column['filterValue'],
                $column['locale'] ?? null
            );
        }
    }

    public function getSimpleColumnFilterByType(string $type): ?SimpleColumnFilter
    {
        $columns  = array_filter(
            $this->columnFilters,
            static fn ($columnFilter) => $columnFilter['type'] === $type
        );

        if (count($columns) > 1) {
            throw new InvalidArgumentException('More than one filter of same type is not allowed');
        }

        $column = reset($columns);

        if (isset($column['filterValue'])) {
            return new SimpleColumnFilter($type, $column['filterValue']);
        }

        return null;
    }

    public function getFirstColumnFilterByType(string $type): ?ColumnFilter
    {
        $columns = iterator_to_array($this->getColumnFilterByType($type));
        $column = reset($columns);

        return $column ?: null;
    }

    public function getColumnFilters(): array
    {
        return $this->columnFilters;
    }

    public function getSortFilter(): SortFilter
    {
        return $this->sortFilter;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(?string $className): void
    {
        $this->className = $className;
    }

    public function getClassId(): ?string
    {
        return $this->classId;
    }

    public function setClassId(?string $classId): void
    {
        $this->classId = $classId;
    }
}
