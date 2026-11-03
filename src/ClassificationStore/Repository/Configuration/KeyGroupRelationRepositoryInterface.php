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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactAssignmentEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing;

/**
 * @internal
 */
interface KeyGroupRelationRepositoryInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function getListingByGroupId(FilterParameter $parameters, int $groupId): Listing;

    /**
     * One page of the store's key assignments, ordered by the identifier pair, so that a
     * configuration transfer never holds a whole store at once.
     *
     * @return ScopedKeyAssignment[]
     *
     * @throws DatabaseException
     */
    public function getChunkByStore(int $storeId, int $limit, int $offset): array;

    /**
     * The names of the groups the key is still assigned to - what a configuration that disables a
     * key has to be refused over, because writing `enabled = 0` drops every one of those rows.
     *
     * @return string[]
     *
     * @throws DatabaseException
     */
    public function getGroupNamesByKeyId(int $keyId): array;

    /**
     * The write a configuration transfer goes through, carrying the per assignment columns a
     * created or updated assignment supplies. A column the entry leaves out is not set, so the
     * stored value stays.
     *
     * @throws ElementSavingFailedException
     */
    public function createOrUpdateFromArtifact(
        ArtifactAssignmentEntry $entry,
        int $keyId,
        int $groupId,
    ): KeyGroupRelation;

    /**
     * @throws ElementSavingFailedException
     */
    public function createOrUpdate(int $keyId, int $groupId, int $sorter, bool $mandatory): KeyGroupRelation;

    /**
     * @throws Exception
     */
    public function delete(int $keyId, int $groupId): void;
}
