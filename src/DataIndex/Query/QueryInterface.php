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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Query;

use Carbon\Carbon;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Model\UserInterface;

interface QueryInterface
{
    public function setPage(int $page): self;

    public function setPageSize(int $pageSize): self;

    public function filterParentId(?int $parentId): self;

    public function filterPath(string $path, bool $includeDescendants, bool $includeParent): self;

    public function setSearchTerm(?string $term): self;

    public function excludeFolders(): self;

    public function getSearch(): SearchInterface;

    public function orderByPath(string $direction): self;

    public function searchById(int $id): self;

    public function searchByIds(array $ids): self;

    /**
     * @param array<int> $tags
     */
    public function filterTags(array $tags, bool $considerChildTags): self;

    public function filterByPql(string $pqlQuery): self;

    public function setUser(UserInterface $user): self;

    public function filterInteger(string $field, int $value): self;

    public function filterFullText(string $value): self;

    public function filterMultiMatch(
        string $searchTerm,
        array $fields = [],
        string $type = 'best_fields',
        string $operator = 'or'
    ): self;

    public function orderByField(string $fieldName, SortDirection $direction): self;

    public function wildcardSearch(
        string $fieldName,
        string $searchTerm,
        bool $enablePqlFieldNameResolution = true
    ): self;

    public function filterDatetime(
        string $field,
        Carbon|int|null $startDate = null,
        Carbon|int|null $endDate = null,
        Carbon|int|null $onDate = null,
        bool $roundToDay = true,
        bool $enablePqlFieldNameResolution = true
    ): self;

    public function filterTime(
        string $field,
        string|null $startTime = null,
        string|null $endTime = null,
        string|null $onTime = null,
        bool $enablePqlFieldNameResolution = true
    ): self;

    public function filterNumber(
        string $fieldName,
        int|float $searchTerm,
        bool $enablePqlFieldNameResolution = true
    ): self;

    public function filterNumberRange(
        string $fieldName,
        int|float|null $min = null,
        int|float|null $max = null,
        bool $enablePqlFieldNameResolution = true
    ): self;

    public function filterMultiSelect(
        string $fieldName,
        array $values,
        bool $enablePqlFieldNameResolution = true
    ): self;

    public function booleanFilter(string $fieldName, array $values): self;
}
