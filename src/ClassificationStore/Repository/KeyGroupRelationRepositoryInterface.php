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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * @internal
 */
interface KeyGroupRelationRepositoryInterface
{
    /**
     * Ordered by groupId ASC, then sorter ASC, then id ASC.
     *
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
     * Ordered by sorter ASC, then id ASC.
     *
     * @param int[] $groupIds
     *
     * @return KeyGroupRelation[] of all given groups
     */
    public function getByGroupIds(array $groupIds): array;

    /**
     * The `localized` flag of each of the given keys, by key id, read in ONE statement.
     *
     * Core's relation listing already joins the key's columns but keeps only those its relation model
     * has a property for, and it has none for this one, so a listing that publishes the flag has to
     * fetch it. Fetching it per row is what this exists to avoid: `KeyConfig::getById()` queries on
     * a cache miss, so a cold page of N keys would cost N statements.
     *
     * Only the keys that have a row are present, and a key present with null states no flag of its
     * own. Keys are read whether enabled or not, because the listing lists them either way.
     *
     * @param int[] $keyIds duplicates and an empty list are allowed
     *
     * @return array<int, bool|null>
     *
     * @throws DatabaseException
     */
    public function getKeyLocalizationFlags(array $keyIds): array;
}
