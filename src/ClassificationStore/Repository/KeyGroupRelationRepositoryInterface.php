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

use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * @internal
 */
interface KeyGroupRelationRepositoryInterface
{
    /**
     * @return KeyGroupRelation[]
     */
    public function getPaginatedKeyGroupRelationByStore(
        int $storeId,
        CollectionParametersInterface $collectionParameters,
        ?array $groupIds = null,
        ?string $searchTerm = null
    ): array;

    public function getCountByStoreId(int $storeId, ?array $groupIds = null, ?string $searchTerm = null): int;

    /**
     * @param int[] $groupIds
     *
     * @return KeyGroupRelation[] of all given groups, in id ascending order
     */
    public function getByGroupIds(array $groupIds): array;
}
