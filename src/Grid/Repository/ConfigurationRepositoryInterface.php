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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Repository;

use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface ConfigurationRepositoryInterface
{
    /**
     * @throws NotFoundException
     */
    public function getById(int $id): GridConfiguration;

    public function create(GridConfiguration $configuration): GridConfiguration;

    public function update(GridConfiguration $configuration): GridConfiguration;

    public function clearShares(GridConfiguration $configuration): GridConfiguration;

    /**
     * @return GridConfiguration[]
     */
    public function getByAssetFolderId(int $folderId): array;

    /**
     * @return GridConfiguration[]
     */
    public function getForAsset(): array;

    /**
     * @return GridConfiguration[]
     */
    public function getByClassId(string $classId): array;

    /**
     * Returns all grid configurations (asset and data object) across all users, paginated.
     *
     * @param int[] $ownerIds
     * @param int[] $excludeOwnerIds
     * @param array<array{field?: string, direction?: string}> $sortBy ordered sort instructions
     *
     * @return GridConfiguration[]
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

    public function delete(GridConfiguration $configuration): void;
}
