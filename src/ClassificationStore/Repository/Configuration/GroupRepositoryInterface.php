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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Listing;

/**
 * @internal
 */
interface GroupRepositoryInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function getListing(FilterParameter $parameters, int $storeId): Listing;

    /**
     * @throws NotFoundException
     */
    public function getById(int $id): GroupConfig;

    /**
     * One page of the store's groups, ordered by identifier and taken after the identifier and
     * row id of the last row handed back, so that a configuration transfer never holds a whole
     * store at once.
     *
     * A cursor rather than an offset: an export is a read per page per section, and a row deleted
     * between two of them shifts every later row one place towards the start - which an offset then
     * resumes past, leaving the shifted row out of a download that reports success. Null for
     * `$afterIdentifier` is the first page.
     *
     * @return GroupConfig[]
     */
    public function getChunkAfterIdentifier(
        int $storeId,
        ?string $afterIdentifier,
        int $afterId,
        int $limit,
    ): array;

    /**
     * One page of the store's groups, taken after the given row id, which is the paging an apply
     * walks the target in - it writes while it walks, and an offset into an identifier ordering
     * would skip the rows an adopted identifier moved past.
     *
     * `$withIdentifier` is the identifier phase the page covers: true for the rows that carry an
     * identifier, false for the rows that do not, null for every row in one walk. An apply takes
     * the two phases in that order, so that the row owning an entry's identifier claims it before
     * an identifierless namesake can be adopted onto the wrong row.
     *
     * @return GroupConfig[]
     */
    public function getChunkAfterId(
        int $storeId,
        int $afterId,
        int $limit,
        ?bool $withIdentifier = null,
    ): array;

    /**
     * Holds every group row of the store for the rest of the caller's transaction, ascending by id.
     *
     * It exists for the lock order, not for a decision of its own: groups before sub groups before
     * relations is the order every writer of this family takes these three tables in -
     * {@see SubGroupRepositoryInterface::delete()} holds the groups of the store and then the
     * vocabulary row, {@see KeyGroupRelationRepositoryInterface::createOrUpdate()} holds the group
     * and then the relation. A caller that writes sub groups before groups has to reach the group
     * rows first through this, or it holds a sub group while waiting for a group that a sub group
     * deletion already holds - the cycle InnoDB resolves by aborting one of the two writers, which
     * nothing retries.
     *
     * Outside a transaction it holds nothing beyond the statement, which is what a read-only caller
     * wants from it.
     *
     * @throws DatabaseException
     */
    public function holdGroupsOfStore(int $storeId): void;

    /**
     * Whether a group of the store already carries the name, which is the rule
     * {@see self::create()} refuses a write over - a rename is not checked, because
     * {@see self::update()} does not check one either. Asked before an artifact is applied, so that
     * the collision is reported as a conflict of the reconcile rather than written past the rule.
     *
     * @throws DatabaseException
     */
    public function isNameTaken(int $storeId, string $name): bool;

    /**
     * Writes the artifact's values onto the given configuration, or onto a new one when there is
     * none, stamping the identifier on a row that does not carry one yet.
     *
     * @throws ElementSavingFailedException
     */
    public function saveFromArtifact(
        ?GroupConfig $config,
        int $storeId,
        string $identifier,
        string $name,
        ?string $description,
    ): GroupConfig;

    /**
     * @throws InvalidArgumentException
     * @throws ElementSavingFailedException
     */
    public function create(string $name, int $storeId): GroupConfig;

    /**
     * @throws NotFoundException
     * @throws ElementSavingFailedException
     */
    public function update(int $id, string $name, ?string $description): GroupConfig;

    /**
     * @throws NotFoundException
     */
    public function delete(int $id): void;
}
