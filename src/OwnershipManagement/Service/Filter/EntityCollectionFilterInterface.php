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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\Filter;

/**
 * Shared ownership-aware querying (free-text search, owner filtering, sorting, pagination) for
 * user-owned configuration entities, so each configuration repository can expose these operations
 * without duplicating the Doctrine query building.
 *
 * @internal
 */
interface EntityCollectionFilterInterface
{
    /**
     * @template T of object
     *
     * @param class-string<T> $entityClass
     * @param int[] $ownerIds
     * @param int[] $excludeOwnerIds
     * @param array<array{field?: string, direction?: string}> $sortBy ordered sort instructions
     *
     * @return T[]
     */
    public function findAllPaginated(
        string $entityClass,
        int $offset,
        int $limit,
        ?string $searchTerm = null,
        array $ownerIds = [],
        array $excludeOwnerIds = [],
        array $sortBy = [],
    ): array;

    /**
     * @param class-string $entityClass
     * @param int[] $ownerIds
     * @param int[] $excludeOwnerIds
     */
    public function countAll(
        string $entityClass,
        ?string $searchTerm = null,
        array $ownerIds = [],
        array $excludeOwnerIds = [],
    ): int;

    /**
     * @param class-string $entityClass
     *
     * @return int[]
     */
    public function getDistinctOwnerIds(string $entityClass): array;
}
