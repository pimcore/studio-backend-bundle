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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;

/**
 * @internal
 */
interface GroupConfigRepositoryInterface
{
    /**
     * @param array<int, int>|null $groupIds
     *
     * @return GroupConfig[]
     */
    public function getPaginatedGroupsByStore(
        int $storeId,
        CollectionParametersInterface $collectionParameters,
        ?array $groupIds = null,
        ?string $searchTerm = null
    ): array;

    public function getAllGroupsByStore(
        int $storeId,
        ?array $groupIds = null
    ): array;

    public function getCountByStoreId(int $storeId): int;

    /**
     * @throws NotFoundException
     */
    public function getById(int $id): GroupConfig;
}
