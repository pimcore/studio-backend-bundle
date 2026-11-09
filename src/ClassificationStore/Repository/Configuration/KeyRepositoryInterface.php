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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactKeyEntry;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Listing;

/**
 * @internal
 */
interface KeyRepositoryInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function getListing(FilterParameter $parameters, int $storeId): Listing;

    /**
     * @throws NotFoundException
     */
    public function getById(int $id): KeyConfig;

    /**
     * One page of the store's keys, ordered by identifier and taken after the identifier and
     * row id of the last row handed back, so that a configuration transfer never holds a whole
     * store at once.
     *
     * A cursor rather than an offset: an export is a read per page per section, and a row deleted
     * between two of them shifts every later row one place towards the start - which an offset then
     * resumes past, leaving the shifted row out of a download that reports success. Null for
     * `$afterIdentifier` is the first page.
     *
     * `$disabledOnly` is the state the page covers: the listing's own `includeDisabled` narrows
     * to `(enabled is null or enabled = 0)` rather than widening to every row, so full coverage of
     * a store is the two walks together.
     *
     * @return KeyConfig[]
     */
    public function getChunkAfterIdentifier(
        int $storeId,
        bool $disabledOnly,
        ?string $afterIdentifier,
        int $afterId,
        int $limit,
    ): array;

    /**
     * One page of the store's keys in the given state, taken after the given row id, which is the
     * paging an apply walks the target in - it writes while it walks, and writing `enabled` moves a
     * key out of the state its walk covers, which an offset into that walk would then skip past.
     *
     * `$withIdentifier` is the identifier phase the page covers: true for the rows that carry an
     * identifier, false for the rows that do not, null for every row in one walk. An apply takes
     * the two phases in that order, so that the row owning an entry's identifier claims it before
     * an identifierless namesake can be adopted onto the wrong row.
     *
     * @return KeyConfig[]
     */
    public function getChunkAfterId(
        int $storeId,
        bool $disabledOnly,
        int $afterId,
        int $limit,
        ?bool $withIdentifier = null,
    ): array;

    /**
     * Writes the artifact's key entry onto the given configuration, or onto a new one when there is
     * none, stamping the identifier on a row that does not carry one yet.
     *
     * Answers with the definition the row carried when the write took its lock, which is the only
     * value the key's resolved search index flag may be compared against: see
     * {@see self::update()} for why a definition the caller read earlier is not one.
     *
     * @throws ElementSavingFailedException
     */
    public function saveFromArtifact(?KeyConfig $config, int $storeId, ArtifactKeyEntry $entry): KeyWriteResult;

    /**
     * @throws ElementSavingFailedException
     */
    public function create(string $name, int $storeId, ?string $title = null): KeyConfig;

    /**
     * The title and the description are written only when the payload supplied them, a supplied
     * empty one clearing the stored value; the definition is written only when it is not null.
     * The `customMetadata` is written only when the payload supplied it, a supplied null clearing it.
     *
     * The write holds the key row, and the result carries the definition that row carried when the
     * lock was taken. That is what the caller compares the key's resolved search index flag
     * against: a definition the caller read a call earlier belongs to a snapshot a competing
     * writer can have replaced in between, and comparing against it drops the very flag change it
     * was read for ({@see KeyWriteResult}).
     *
     * @throws NotFoundException
     * @throws ElementSavingFailedException
     */
    public function update(int $id, KeyUpdate $parameters): KeyWriteResult;

    /**
     * @throws NotFoundException
     * @throws ElementSavingFailedException
     */
    public function softDelete(int $id): void;

    /**
     * @throws NotFoundException
     */
    public function getPageForId(
        string $table,
        int $id,
        int $storeId,
        int $pageSize,
        string $sortKey,
        string $sortDir,
    ): int;

    /**
     * The ids of the keys of the store that carry one of the given identifiers, in ONE statement.
     *
     * Only the identifiers that resolve are present; a row without an identifier is never matched.
     *
     * @param string[] $identifiers duplicates and an empty list are allowed
     *
     * @return array<string, int> id by identifier
     *
     * @throws DatabaseException
     */
    public function getIdsByIdentifiers(int $storeId, array $identifiers): array;

    /**
     * The identifiers of the given keys of the store, in ONE statement.
     *
     * Only the rows that carry an identifier are present.
     *
     * @param int[] $ids duplicates and an empty list are allowed
     *
     * @return array<int, string> identifier by id
     *
     * @throws DatabaseException
     */
    public function getIdentifiersByIds(int $storeId, array $ids): array;
}
