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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Model\Exception\InvalidConfigException;

/**
 * One stored row as one artifact entry: that row's own columns, and nothing a reader could not
 * apply somewhere else - no row id, no store id, no endpoint id, no timestamp.
 *
 * @internal
 */
interface ArtifactEntryMapperInterface
{
    /**
     * The store's `engine` is deliberately absent: flipping a store's storage engine moves stored
     * values and is a migration of its own, not a configuration a file carries.
     *
     * @return array<string, mixed>
     */
    public function mapStore(StoreConfig $store): array;

    /**
     * The sub group's, collection's and group's `customMetadata` is always written, null where the row
     * holds none, and so is the key's.
     *
     * @return array<string, mixed>
     */
    public function mapSubGroup(SubGroupConfig $config): array;

    /**
     * @return array<string, mixed>
     */
    public function mapCollection(CollectionConfig $config): array;

    /**
     * The group's `parentId` is absent - a group tree is not part of what a store configuration
     * carries.
     *
     * @return array<string, mixed>
     */
    public function mapGroup(GroupConfig $config): array;

    /**
     * The definition travels decoded and verbatim, which is what carries the key's title through a
     * core that re-derives the column from the blob.
     *
     * `localized` is written only where the key states a flag: a key that states none produces an
     * entry with no such member, the entry a file written before the flag existed also carries.
     *
     * @return array<string, mixed>
     */
    public function mapKey(KeyConfig $config): array;

    /**
     * @return array<string, mixed>
     */
    public function mapCollectionRelation(ScopedCollectionRelation $relation): array;

    /**
     * The own columns of the relation only. `name`, `description`, `definition`, `type`, `enabled`
     * and `groupName` are joined from the key and the group, and belong to those entries.
     *
     * @return array<string, mixed>
     */
    public function mapKeyAssignment(ScopedKeyAssignment $assignment): array;

    /**
     * The other direction: the relation an artifact entry is written through. A per assignment
     * column is set only when the entry supplies it, because setting one is what marks it as
     * initialized, and the model writes a column only once it was. `sorter` and `mandatory` have no
     * such guard and are always set.
     *
     * @throws InvalidConfigException
     */
    public function mapToAssignment(ArtifactAssignmentEntry $entry, int $keyId, int $groupId): KeyGroupRelation;
}
