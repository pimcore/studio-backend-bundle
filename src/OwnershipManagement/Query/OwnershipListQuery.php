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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query;

/**
 * Normalized listing criteria handed to ownership management providers. It exposes only the values a
 * provider needs (pagination, free-text search, the deleted-owner filter and sorting), so providers do
 * not depend on the internal request filter representation.
 */
final readonly class OwnershipListQuery
{
    /**
     * @param OwnershipSort[] $sortBy ordered list of sort instructions (primary first, then tie-breakers)
     */
    public function __construct(
        private int $offset,
        private int $limit,
        private ?string $searchTerm = null,
        private bool $includeDeletedOwners = true,
        private array $sortBy = [],
    ) {
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }

    public function includeDeletedOwners(): bool
    {
        return $this->includeDeletedOwners;
    }

    /**
     * @return OwnershipSort[]
     */
    public function getSortBy(): array
    {
        return $this->sortBy;
    }
}
