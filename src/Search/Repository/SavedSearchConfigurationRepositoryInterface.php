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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Repository;

use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface SavedSearchConfigurationRepositoryInterface
{
    /**
     * @throws NotFoundException
     */
    public function getById(int $id): SavedSearchConfiguration;

    /**
     * @return SavedSearchConfiguration[]
     */
    public function getList(?string $searchTerm): array;

    public function create(SavedSearchConfiguration $configuration): SavedSearchConfiguration;

    public function update(SavedSearchConfiguration $configuration): SavedSearchConfiguration;

    public function clearShares(SavedSearchConfiguration $configuration): SavedSearchConfiguration;

    /**
     * Returns all saved search configurations across all users, paginated.
     *
     * @param int[] $ownerIds
     * @param int[] $excludeOwnerIds
     * @param array<array{field?: string, direction?: string}> $sortBy ordered sort instructions
     *
     * @return SavedSearchConfiguration[]
     */
    public function findAllPaginated(
        int $offset,
        int $limit,
        ?string $searchTerm = null,
        array $ownerIds = [],
        array $excludeOwnerIds = [],
        array $sortBy = [],
    ): array;

    /**
     * @param int[] $ownerIds
     * @param int[] $excludeOwnerIds
     */
    public function countAll(?string $searchTerm = null, array $ownerIds = [], array $excludeOwnerIds = []): int;

    /**
     * @return int[]
     */
    public function getDistinctOwnerIds(): array;

    public function delete(SavedSearchConfiguration $configuration): void;
}
