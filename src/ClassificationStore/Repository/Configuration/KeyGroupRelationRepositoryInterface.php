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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactAssignmentEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
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
     * One page of the store's key assignments, ordered by the identifier pair and taken after the last
     * row handed back, so that a configuration transfer never holds a whole store at once.
     *
     * A cursor rather than an offset: a transfer is a read per page, and a relation deleted between
     * two of them shifts every later one towards the start - which an offset then resumes past,
     * leaving the shifted row out of an export that reports success. Null for `$after` is the first
     * page.
     *
     * @return ScopedKeyAssignment[]
     *
     * @throws DatabaseException
     */
    public function getChunkAfter(int $storeId, ?ScopedKeyAssignment $after, int $limit): array;

    /**
     * The names of the groups the key is still assigned to - what a configuration that disables a
     * key has to be refused over, because writing `enabled = 0` drops every one of those rows.
     *
     * `$hold` is set by a caller that is about to write under the answer. It reads the assignments
     * as of now rather than from the snapshot of the transaction it runs in, and keeps them: a plain
     * read answers from a view that can predate an assignment a competing writer has already
     * committed, and would report a key unassigned that is not - leaving the disable to drop the row
     * the refusal exists to protect. A caller that writes nothing asks without it, because it has
     * nothing to protect and a dry run has no transaction for a lock to live in.
     *
     * The relation rows this reads hang off the group rows the caller is expected to hold already
     * ({@see GroupRepositoryInterface::holdGroupsOfStore()}), which is what keeps a held read here
     * in the lock order of this family rather than across it.
     *
     * @return string[]
     *
     * @throws DatabaseException
     */
    public function getGroupNamesByKeyId(int $keyId, bool $hold = false): array;

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
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function createOrUpdate(KeyGroupRelationCreate $parameters): KeyGroupRelation;

    /**
     * @throws Exception
     */
    public function delete(int $keyId, int $groupId): void;
}
