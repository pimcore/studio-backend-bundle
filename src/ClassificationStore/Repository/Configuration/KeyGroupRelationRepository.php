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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexFlagResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactAssignmentEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryMapperInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao as KeyConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao as KeyGroupRelationDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Dao as SubGroupConfigDao;
use Pimcore\Model\Exception\InvalidConfigException;
use Throwable;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;

/**
 * @internal
 */
final readonly class KeyGroupRelationRepository implements KeyGroupRelationRepositoryInterface
{
    private const string NOT_FOUND_TYPE = 'key group relation';

    private const int NEW_RELATION_SORTER = 0;

    private const bool NEW_RELATION_MANDATORY = false;

    /**
     * The cursor a transfer pages these assignments by: the identifier pair the artifact sorts them
     * by, with the id pair - the relation's whole primary key - behind it, so that the comparison is
     * total even for a row whose endpoints carry no identifier yet.
     *
     * A cursor rather than an offset, because a transfer is a read per page and an assignment
     * deleted between two of them shifts every later one towards the start, past an offset that has
     * already counted it - which leaves it out of an export that reports success.
     */
    private const string CURSOR_CONDITION = " AND (COALESCE(`g`.`identifier`, ''),"
        . " COALESCE(`k`.`identifier`, ''), `r`.`groupId`, `r`.`keyId`) > (?, ?, ?, ?)";

    public function __construct(
        private ListingFilterInterface $listingFilter,
        private Connection $connection,
        private ArtifactEntryMapperInterface $entryMapper,
        private KeyRepositoryInterface $keyRepository,
        private SearchIndexFlagResolverInterface $searchIndexFlagResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getListingByGroupId(FilterParameter $parameters, int $groupId): Listing
    {
        $listing = new Listing();
        // own parameter name, so a client supplied equals filter on `groupId` narrows the route's
        // group binding instead of replacing the condition Pimcore keys by its own string
        $listing->addConditionParam('`groupId` = :scopeGroupId', ['scopeGroupId' => $groupId]);

        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    /**
     * {@inheritdoc}
     */
    public function getChunkAfter(int $storeId, ?ScopedKeyAssignment $after, int $limit): array
    {
        $query = sprintf(
            'SELECT `r`.`keyId`, `r`.`groupId`, `r`.`sorter`, `r`.`mandatory`, `r`.`metadata`,'
            . ' `r`.`definitionOverride`, `r`.`subGroup`,'
            . ' `g`.`identifier` AS `groupIdentifier`, `k`.`identifier` AS `keyIdentifier`'
            . ' FROM `%s` `r`'
            . ' INNER JOIN `%s` `g` ON `g`.`id` = `r`.`groupId`'
            . ' INNER JOIN `%s` `k` ON `k`.`id` = `r`.`keyId`'
            . ' WHERE `g`.`storeId` = ?%s'
            . ' ORDER BY `g`.`identifier`, `k`.`identifier`, `r`.`groupId`, `r`.`keyId`'
            . ' LIMIT ?',
            KeyGroupRelationDao::TABLE_NAME_RELATIONS,
            GroupConfigDao::TABLE_NAME_GROUPS,
            KeyConfigDao::TABLE_NAME_KEYS,
            $after === null ? '' : self::CURSOR_CONDITION
        );

        $parameters = [$storeId];
        $types = [ParameterType::INTEGER];

        if ($after !== null) {
            $cursor = $after->getRelation();
            $parameters[] = $after->getGroupIdentifier();
            $parameters[] = $after->getKeyIdentifier();
            $parameters[] = (int) $cursor->getGroupId();
            $parameters[] = (int) $cursor->getKeyId();
            $types[] = ParameterType::STRING;
            $types[] = ParameterType::STRING;
            $types[] = ParameterType::INTEGER;
            $types[] = ParameterType::INTEGER;
        }

        $parameters[] = $limit;
        $types[] = ParameterType::INTEGER;

        $assignments = [];

        try {
            // the paging bounds are bound as integers, which is what the server needs them to be
            $rows = $this->connection->fetchAllAssociative($query, $parameters, $types);

            foreach ($rows as $row) {
                $assignments[] = new ScopedKeyAssignment(
                    $this->hydrateRelation($row),
                    (string) $row['groupIdentifier'],
                    (string) $row['keyIdentifier']
                );
            }
        } catch (DBALException|Exception $e) {
            throw new DatabaseException('Failed to read the key assignments: ' . $e->getMessage(), $e);
        }

        return $assignments;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupNamesByKeyId(int $keyId, bool $hold = false): array
    {
        try {
            return $this->connection->fetchFirstColumn(
                sprintf(
                    'SELECT DISTINCT `g`.`name` FROM `%s` `r`'
                    . ' INNER JOIN `%s` `g` ON `g`.`id` = `r`.`groupId`'
                    . ' WHERE `r`.`keyId` = ? ORDER BY `g`.`name`%s',
                    KeyGroupRelationDao::TABLE_NAME_RELATIONS,
                    GroupConfigDao::TABLE_NAME_GROUPS,
                    $hold ? ' FOR UPDATE' : ''
                ),
                [$keyId]
            );
        } catch (DBALException $e) {
            throw new DatabaseException('Failed to read the groups of the key: ' . $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createOrUpdateFromArtifact(
        ArtifactAssignmentEntry $entry,
        int $keyId,
        int $groupId,
    ): KeyGroupRelation {
        try {
            // the mapper sets only what the entry supplies, which is what the model's own
            // initialized guards gate the write on
            $relation = $this->entryMapper->mapToAssignment($entry, $keyId, $groupId);
            $relation->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }

        return $relation;
    }

    /**
     * {@inheritdoc}
     *
     * The three per assignment properties are written only when the payload carried them: core skips a
     * column whose setter was never called ({@see KeyGroupRelationDao::update()} through its
     * initialization guard), so an absent property leaves the stored value alone while an explicit null
     * clears it.
     *
     * `sorter` and `mandatory` follow the same rule, but they reach it from this side: core has no
     * initialization guard for them - they are plain `int` and `bool` properties defaulting to 0 and
     * false ({@see KeyGroupRelation}), so every save writes both columns whether or not a setter ran.
     * The stored values are therefore read here and re-supplied for a property the payload omitted,
     * which is what {@see self::holdRelation()} answers with; a pair with no row yet takes the defaults
     * of a new relation instead, so the released payload still creates an assignment sorted first and
     * not mandatory.
     *
     * The write and the read-back are one unit of work, with the assignment row held from before the
     * write until the unit commits. The instance saved here carries only the properties the payload
     * named, so the ones the write left alone are reported from the stored row - and a competing writer
     * committing in between would otherwise have the response name values this request never wrote.
     *
     * An enclosing transaction stays the caller's to finish, the way core's own writes join one
     * ({@see \Pimcore\Model\DataObject\Classificationstore\Traits\AtomicWriteTrait}) - and core's write
     * joins this one, so the save neither opens nor commits a transaction of its own.
     *
     * An answer the API already has a status for passes through, which is what keeps the 422 of a
     * refused sub group or definition override from arriving as the 500 a failed save is.
     *
     * Creating an assignment is also the one moment the key has a say in its `metadata`: a pair with
     * no row yet takes the search index flag of its key where the payload leaves it without one
     * ({@see self::seedSearchIndexFlag()}). An update never consults the key.
     */
    public function createOrUpdate(KeyGroupRelationCreate $parameters): KeyGroupRelation
    {
        $keyId = $parameters->getKeyId();
        $groupId = $parameters->getGroupId();

        // an enclosing transaction stays the caller's to finish, the way core's own writes join one
        $ownTransaction = !$this->connection->isTransactionActive();
        if ($ownTransaction) {
            $this->connection->beginTransaction();
        }

        try {
            $this->holdGroup($groupId);
            $stored = $this->holdRelation($keyId, $groupId);

            $this->applyAndSave($keyId, $groupId, $parameters, $stored);

            $relation = $this->getByKeyAndGroup($keyId, $groupId);
        } catch (Throwable $e) {
            if ($ownTransaction && $this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw match (true) {
                $e instanceof AbstractApiException => $e,
                $e instanceof InvalidConfigException => new InvalidArgumentException($e->getMessage(), $e),
                $e instanceof Exception => new ElementSavingFailedException(null, $e->getMessage(), $e),
                default => $e,
            };
        }

        if ($ownTransaction) {
            $this->connection->commit();
        }

        return $relation;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $keyId, int $groupId): void
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId($groupId);
        $relation->delete();
    }

    /**
     * The stored row as the model carries it: the own columns only, the two JSON ones decoded. The
     * three per assignment columns are set from the row, which is what marks them as loaded.
     *
     * @param array<string, mixed> $row
     */
    private function hydrateRelation(array $row): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId((int) $row['keyId']);
        $relation->setGroupId((int) $row['groupId']);
        $relation->setSorter((int) $row['sorter']);
        $relation->setMandatory((bool) $row['mandatory']);
        $relation->setMetadata($this->decodeJsonColumn($row['metadata'] ?? null));
        $relation->setDefinitionOverride($this->decodeJsonColumn($row['definitionOverride'] ?? null));

        $subGroup = $row['subGroup'] ?? null;
        $relation->setSubGroup(is_string($subGroup) ? $subGroup : null);

        return $relation;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonColumn(mixed $value): ?array
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Holds the group of the assignment before anything else, because groups before sub groups before
     * relations is the order every writer of this family takes these three tables in:
     * {@see KeyGroupRelationDao::update()} locks the group of the relation and then the entry its tag
     * names, {@see GroupConfigDao::update()} locks the group and then the relations that carry a tag,
     * and {@see SubGroupConfigDao::delete()} locks the groups of the store before the entry. Taking the
     * relation first and reaching the group afterwards - through the save this fronts - closes a cycle
     * that InnoDB resolves by aborting one of the two writers with a deadlock that nothing here retries.
     *
     * It is also what allows the relation to be held before the sub group entry rather than after it:
     * every writer that takes such an entry passes a group row first, so none of them can hold an entry
     * this transaction is waiting for while itself waiting for the group this transaction already holds.
     *
     * A group id no group carries locks nothing here, which is the 404 the read of the assignment
     * answers with a moment later.
     *
     * @throws DBALException
     */
    private function holdGroup(int $groupId): void
    {
        $this->connection->fetchFirstColumn(
            sprintf('SELECT `id` FROM `%s` WHERE `id` = ? FOR UPDATE', GroupConfigDao::TABLE_NAME_GROUPS),
            [$groupId]
        );
    }

    /**
     * Holds the addressed assignment row for the rest of the transaction, and answers with the two
     * released properties it carries - the only ones the write has to re-supply, because core writes
     * their columns unconditionally. The joined row {@see self::getByKeyAndGroup()} reads is the one the
     * response is built from; this reads the two columns off the row it is locking anyway rather than
     * taking a statement of its own for them.
     *
     * It is taken before the write so that the read after it answers with what the writer this
     * transaction waited for left behind: this and {@see self::holdGroup()} are locking reads, which
     * take no consistent-read view of their own, so the view the plain read establishes is the
     * transaction's first and is created once the lock is already held - and the row cannot move while
     * it is.
     *
     * That ordering is what a transaction opened here gives. An enclosing one brought by a caller may
     * already carry a view older than this lock, and a read under it would answer from that view; there
     * is no such caller today, and one would have to read the assignment under its own lock instead.
     *
     * A pair with no row yet locks the gap it would be inserted into, which is what makes two creates of
     * the same pair run one after the other rather than together.
     *
     * The row this holds is the row core's own upsert writes ({@see KeyGroupRelationDao::update()}), so
     * every write of this assignment contends here, whichever endpoint it arrived through.
     *
     * @return array<string, mixed>|null the stored row's `sorter` and `mandatory`, null for no such row
     *
     * @throws DBALException
     */
    private function holdRelation(int $keyId, int $groupId): ?array
    {
        $stored = $this->connection->fetchAssociative(
            sprintf(
                'SELECT `sorter`, `mandatory` FROM `%s` WHERE `keyId` = ? AND `groupId` = ? FOR UPDATE',
                KeyGroupRelationDao::TABLE_NAME_RELATIONS
            ),
            [$keyId, $groupId]
        );

        return $stored === false ? null : $stored;
    }

    /**
     * Only a property the payload carried reaches core's setter, so one it did not carry leaves the
     * column of an already stored row alone - except for the two whose columns core writes whichever
     * setters ran, which are re-supplied from the stored row instead.
     *
     * Both columns are nullable and a relation written before they were set carries NULL in them, which
     * reads as the value a new relation takes: the same coercion core's own load applies
     * ({@see KeyGroupRelationDao::getById()} casts `mandatory` to bool).
     *
     * A created assignment takes the search index flag of its key where the payload leaves it
     * without one, which is the one place the key's `definition` reaches this write
     * ({@see self::seedSearchIndexFlag()}).
     *
     * @param array<string, mixed>|null $stored the stored row's `sorter` and `mandatory`, null for none
     *
     * @throws Throwable
     */
    private function applyAndSave(
        int $keyId,
        int $groupId,
        KeyGroupRelationCreate $parameters,
        ?array $stored,
    ): void {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId($groupId);
        $relation->setSorter(
            $parameters->hasSorter()
                ? $parameters->getSorter()
                : (int) ($stored['sorter'] ?? self::NEW_RELATION_SORTER)
        );
        $relation->setMandatory(
            $parameters->hasMandatory()
                ? $parameters->isMandatory()
                : (bool) ($stored['mandatory'] ?? self::NEW_RELATION_MANDATORY)
        );

        if ($parameters->hasDefinitionOverride()) {
            $relation->setDefinitionOverride($parameters->getDefinitionOverride());
        }

        if ($parameters->hasSubGroup()) {
            $relation->setSubGroup($parameters->getSubGroup());
        }

        $seeded = $stored === null ? $this->seedSearchIndexFlag($keyId, $parameters) : null;

        if ($seeded !== null) {
            $relation->setMetadata($seeded);
        } elseif ($parameters->hasMetadata()) {
            $relation->setMetadata($parameters->getMetadata());
        }

        $relation->save();
    }

    /**
     * The `metadata` a created assignment takes from its key, or null where there is nothing to
     * seed: the key's default fills in for an `indexed` the payload would leave the assignment
     * without, whether its `metadata` is absent, an explicit null, or an object naming other
     * members - the supplied members survive alongside it.
     *
     * The absent and the null case are deliberately the same here, unlike everywhere else in this
     * write: an exported assignment always carries an explicit `metadata`
     * ({@see ArtifactEntryMapperInterface::mapKeyAssignment()}), so refusing null would leave an
     * exported and reimported store with nothing ever seeded.
     *
     * Only a create reaches this. An update has a prior value to keep or to clear, and the key is
     * not consulted for it - which is also why an explicit `indexed` in the payload is the only
     * thing that overrides the key, and why a key stating no default seeds nothing at all.
     *
     * @return array<string, mixed>|null
     *
     * @throws NotFoundException
     */
    private function seedSearchIndexFlag(int $keyId, KeyGroupRelationCreate $parameters): ?array
    {
        $metadata = $parameters->hasMetadata() ? $parameters->getMetadata() : null;

        if ($this->searchIndexFlagResolver->carriesFlag($metadata)) {
            return null;
        }

        return $this->searchIndexFlagResolver->withKeyDefault(
            $metadata,
            $this->keyRepository->getById($keyId)->getDefinition()
        );
    }

    /**
     * The stored row is loaded here rather than taken from the request memo of the key relation
     * resolver, which hands out clones of what it saw earlier in the request and would load every
     * relation of the group to answer for one pair.
     *
     * It is a plain read, and the relation listing has no locking form to offer; what makes it answer
     * with the current row rather than with a snapshot is that it runs under {@see self::holdRelation()},
     * whose lock is taken first and held throughout.
     *
     * @throws NotFoundException
     */
    private function getByKeyAndGroup(int $keyId, int $groupId): KeyGroupRelation
    {
        $listing = new Listing();
        $listing->addConditionParam('`keyId` = :keyId', ['keyId' => $keyId]);
        $listing->addConditionParam('`groupId` = :groupId', ['groupId' => $groupId]);

        $relations = $listing->load();

        if ($relations === []) {
            throw new NotFoundException(self::NOT_FOUND_TYPE, $keyId);
        }

        return $relations[0];
    }
}
