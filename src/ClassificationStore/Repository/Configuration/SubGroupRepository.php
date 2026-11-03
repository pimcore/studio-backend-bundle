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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\IdentifierOrderedChunkTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao as KeyGroupRelationDao;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig\Dao as StoreConfigDao;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Dao as SubGroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Listing;
use Throwable;
use function array_fill;
use function count;
use function implode;
use function is_string;
use function sprintf;

/**
 * @internal
 */
final readonly class SubGroupRepository implements SubGroupRepositoryInterface
{
    use IdentifierOrderedChunkTrait;

    private const string NOT_FOUND_TYPE = 'sub group configuration';

    /**
     * `:scopeStoreId`, not `:storeId`: a client supplied equals filter on `storeId` builds the very
     * condition string the route binding would, and Pimcore keys its conditions by that string - an
     * identical one replaces the binding instead of being added next to it. Under its own name the
     * two conditions stand side by side, so a filter can only narrow the store.
     */
    private const string STORE_SCOPE_PARAMETER = 'scopeStoreId';

    private const string STORE_SCOPE_CONDITION = '`storeId` = :' . self::STORE_SCOPE_PARAMETER;

    public function __construct(
        private ListingFilterInterface $listingFilter,
        private SearchHelperServiceInterface $searchHelperService,
        private Connection $connection,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getListing(FilterParameter $parameters, int $storeId): Listing
    {
        $listing = new Listing();
        $listing->addConditionParam(self::STORE_SCOPE_CONDITION, [self::STORE_SCOPE_PARAMETER => $storeId]);

        $this->applySearchCondition($listing, $parameters);
        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $id): SubGroupConfig
    {
        $listing = new Listing();
        $listing->addConditionParam('`id` = :id', ['id' => $id]);
        $configs = $listing->load();

        if (empty($configs)) {
            throw new NotFoundException(self::NOT_FOUND_TYPE, $id);
        }

        return $configs[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getByIdentifiers(int $storeId, array $identifiers): array
    {
        if (empty($identifiers)) {
            return [];
        }

        // positional throughout, so that the IN list expands the way the other listings build theirs
        $placeholders = implode(',', array_fill(0, count($identifiers), '?'));

        $listing = new Listing();
        $listing->addConditionParam('`storeId` = ?', [$storeId]);
        $listing->addConditionParam('`identifier` IN (' . $placeholders . ')', $identifiers);

        $resolved = [];
        foreach ($listing->load() as $config) {
            $identifier = $config->getIdentifier();
            if ($identifier === null) {
                continue;
            }

            $resolved[$identifier] = $config;
        }

        return $resolved;
    }

    /**
     * {@inheritdoc}
     */
    public function getChunkByStore(int $storeId, int $limit, int $offset): array
    {
        $listing = new Listing();
        $listing->addConditionParam(self::STORE_SCOPE_CONDITION, [self::STORE_SCOPE_PARAMETER => $storeId]);
        $this->applyIdentifierOrderedChunk($listing, $limit, $offset);

        return $listing->load();
    }

    /**
     * {@inheritdoc}
     */
    public function getChunkAfterId(int $storeId, int $afterId, int $limit): array
    {
        $listing = new Listing();
        $listing->addConditionParam(self::STORE_SCOPE_CONDITION, [self::STORE_SCOPE_PARAMETER => $storeId]);
        $listing->addConditionParam('`id` > :afterId', ['afterId' => $afterId]);
        $this->applyIdCursorChunk($listing, $limit);

        return $listing->load();
    }

    /**
     * {@inheritdoc}
     */
    public function saveFromArtifact(
        ?SubGroupConfig $config,
        int $storeId,
        string $identifier,
        string $name,
        ?string $description,
    ): SubGroupConfig {
        $config ??= new SubGroupConfig();
        $config->setStoreId($storeId);
        $config->setName($name);
        $config->setDescription($description);

        try {
            // before the save, so that a new row carries the file's identifier instead of a fresh one
            $config->setIdentifier($identifier);
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException($config->getId(), $e->getMessage(), $e);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name, int $storeId): SubGroupConfig
    {
        $config = new SubGroupConfig();
        $config->setName($name);
        $config->setStoreId($storeId);

        $this->saveUnderAFreeName($config, $name, $storeId, null);

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, string $name, ?string $description): SubGroupConfig
    {
        $config = $this->getById($id);
        $storeId = $config->getStoreId();

        $config->setName($name);
        $config->setDescription($description);

        $this->saveUnderAFreeName($config, $name, $storeId, $id);

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): void
    {
        $config = $this->getById($id);

        $this->deleteWhileUnused($config, $id);
    }

    /**
     * Probes the assignments and deletes the row as one unit of work, so that the entry the probe
     * finds unused is still unused when the row is deleted.
     *
     * The two used to be separate statements, and the 409 is a decision of this repository alone -
     * core's own delete clears the tags rather than refusing ({@see SubGroupConfigDao::delete()}),
     * and the locks it takes on its way in protect only its own clearing. An assignment committed
     * between the probe and the delete therefore left the delete proceeding on a probe result that
     * was no longer true, and the tag it reported nothing about was cleared without the 409.
     *
     * What makes them exclusive is the vocabulary row itself, taken before the assignments are read
     * and held until the delete commits: it is the row an assignment writer has to take to tag
     * anything with this entry ({@see KeyGroupRelationDao::update()} through its sub group
     * resolution), so a writer racing this delete either committed before the probe read - and is
     * refused with the 409 - or waits for it and then finds the entry gone.
     *
     * The locks are taken in the order every writer of this family takes these three tables in -
     * the groups of the store, then the vocabulary row, then the relations under them - because
     * reaching them the other way round closes a cycle that InnoDB resolves by aborting one of the
     * two writers with a deadlock that nothing here retries.
     *
     * @throws ConflictException
     * @throws DatabaseException
     * @throws NotFoundException
     */
    private function deleteWhileUnused(SubGroupConfig $config, int $id): void
    {
        // an enclosing transaction stays the caller's to finish, the way core's own writes join one
        $ownTransaction = !$this->connection->isTransactionActive();
        if ($ownTransaction) {
            $this->connection->beginTransaction();
        }

        try {
            $this->holdGroupsOf($config->getStoreId());
            $stored = $this->holdSubGroup($id);

            if ($stored === null) {
                throw new NotFoundException(self::NOT_FOUND_TYPE, $id);
            }

            $usedBy = $this->getGroupNamesUsing($stored['storeId'], $stored['identifier']);

            if (!empty($usedBy)) {
                throw new ConflictException(
                    sprintf(
                        'Sub group "%s" is still assigned to keys of the group(s) "%s" and cannot be deleted',
                        $config->getName(),
                        implode('", "', $usedBy)
                    )
                );
            }

            $config->delete();
        } catch (Throwable $e) {
            if ($ownTransaction && $this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw match (true) {
                $e instanceof AbstractApiException => $e,
                $e instanceof DBALException => new DatabaseException(
                    'Failed to delete the sub group: ' . $e->getMessage(),
                    $e
                ),
                default => $e,
            };
        }

        if ($ownTransaction) {
            $this->connection->commit();
        }
    }

    /**
     * Holds the groups of the store before the vocabulary row, because groups before sub groups
     * before relations is the order {@see SubGroupConfigDao::delete()} and
     * {@see KeyGroupRelationDao::update()} take the three tables in, and the usage probe below
     * reads the relations through a join over exactly these group rows.
     *
     * The store is the one the stored entry carries, which a stored entry never changes
     * ({@see SubGroupConfigDao::update()} refuses a save that would), so naming it from the model
     * read a moment ago is sound without a lock on the entry - which is the lock that may not be
     * taken yet.
     *
     * @throws DBALException
     */
    private function holdGroupsOf(int $storeId): void
    {
        // ascending id, so that two deletions of differently scoped entries still agree on an order
        $this->connection->fetchFirstColumn(
            sprintf(
                'SELECT `id` FROM `%s` WHERE `storeId` = ? ORDER BY `id` FOR UPDATE',
                GroupConfigDao::TABLE_NAME_GROUPS
            ),
            [$storeId]
        );
    }

    /**
     * Holds the vocabulary row and reports the store and identifier it actually carries, read as of
     * now rather than from the snapshot of the transaction this runs in - the same current read
     * core's own delete opens with, and the row whose lock an assignment writer has to wait for.
     *
     * A row that is already gone reports nothing, which is the 404 the lookup a moment earlier
     * would have answered with.
     *
     * @return array{storeId: int, identifier: string|null}|null
     *
     * @throws DBALException
     */
    private function holdSubGroup(int $id): ?array
    {
        $stored = $this->connection->fetchAssociative(
            sprintf(
                'SELECT `storeId`, `identifier` FROM `%s` WHERE `id` = ? FOR UPDATE',
                SubGroupConfigDao::TABLE_NAME_SUBGROUPS
            ),
            [$id]
        );

        if ($stored === false) {
            return null;
        }

        $identifier = $stored['identifier'] ?? null;

        return [
            'storeId' => (int) $stored['storeId'],
            'identifier' => is_string($identifier) ? $identifier : null,
        ];
    }

    /**
     * The groups of the store whose key assignments carry the identifier. Scoped to the store
     * because the identifier is unique per store, not globally.
     *
     * Read as of now and locked, the way core's own clearing of these tags reads them
     * ({@see SubGroupConfigDao::delete()}): a plain read would answer from a snapshot that can
     * predate an assignment a competing writer has already committed, and report an entry unused
     * that is not. The group rows this join reads are already held by {@see self::holdGroupsOf()},
     * so the only locks left for it to take are on the relations - after the groups, whichever way
     * the optimizer drives it.
     *
     * @return string[]
     *
     * @throws DatabaseException
     */
    private function getGroupNamesUsing(int $storeId, ?string $identifier): array
    {
        if ($identifier === null) {
            return [];
        }

        try {
            return $this->connection->fetchFirstColumn(
                sprintf(
                    'SELECT DISTINCT `g`.`name` FROM `%s` `r`'
                    . ' INNER JOIN `%s` `g` ON `g`.`id` = `r`.`groupId`'
                    . ' WHERE `g`.`storeId` = ? AND `r`.`subGroup` = ?'
                    . ' FOR UPDATE',
                    KeyGroupRelationDao::TABLE_NAME_RELATIONS,
                    GroupConfigDao::TABLE_NAME_GROUPS
                ),
                [$storeId, $identifier]
            );
        } catch (DBALException $e) {
            throw new DatabaseException('Failed to check the sub group for usage: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Probes the name and writes the row as one unit of work, so that the name the probe finds free is
     * still free when the row is written.
     *
     * The two used to be separate statements, and nothing in the schema enforces the pair: two creates
     * of the same name, or two renames onto it, could both find it free and both persist. What makes
     * them exclusive is the store the name is unique in, taken before the probe and held until the save
     * commits - the second writer waits, then probes against the row the first one left and is refused
     * with the 422 the API promises. The store row is what is locked because it is the scope of the
     * uniqueness, and because reaching a store before the vocabulary rows under it is the order the
     * other writers of this family take their locks in {@see SubGroupConfigDao::delete()}.
     *
     * A schema that does enforce the pair refuses the losing writer itself, which is the same collision
     * arriving as a driver error rather than as a probe result - translated below rather than left to
     * become the 500 a failed save otherwise is.
     *
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     */
    private function saveUnderAFreeName(SubGroupConfig $config, string $name, int $storeId, ?int $id): void
    {
        // an enclosing transaction stays the caller's to finish, the way core's own writes join one
        $ownTransaction = !$this->connection->isTransactionActive();
        if ($ownTransaction) {
            $this->connection->beginTransaction();
        }

        try {
            $this->holdStore($storeId);
            $this->assertNameIsFree($name, $storeId, $id);
            $config->save();
        } catch (Throwable $e) {
            if ($ownTransaction && $this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw match (true) {
                $e instanceof InvalidArgumentException => $e,
                $e instanceof UniqueConstraintViolationException => $this->nameCollisionOrSavingFailed(
                    $e,
                    $name,
                    $storeId,
                    $id
                ),
                $e instanceof Exception => new ElementSavingFailedException($id, $e->getMessage(), $e),
                default => $e,
            };
        }

        if ($ownTransaction) {
            $this->connection->commit();
        }
    }

    /**
     * Holds the store the name is scoped to for the rest of the transaction, so that every create and
     * rename in it runs one after the other.
     *
     * A store id no store carries locks nothing here - that the row may be written anyway is the
     * existing behaviour of this endpoint, which takes the store from the request body rather than from
     * a route that resolves one - and the locking probe below still takes the name itself.
     *
     * @throws DBALException
     */
    private function holdStore(int $storeId): void
    {
        $this->connection->fetchOne(
            sprintf('SELECT `id` FROM `%s` WHERE `id` = ? FOR UPDATE', StoreConfigDao::TABLE_NAME_STORES),
            [$storeId]
        );
    }

    /**
     * The probe reads as of now rather than from the snapshot of the transaction it runs in, the way
     * every other decision in this family that a write depends on does ({@see SubGroupConfigDao::update()}):
     * a plain read would answer from a snapshot that can predate the row a competing writer has already
     * committed, and report a name free that is not. It is also a lock on what it read, which under the
     * default isolation level is the exclusion left where there is no store row to hold.
     *
     * @throws DBALException
     * @throws InvalidArgumentException
     */
    private function assertNameIsFree(string $name, int $storeId, ?int $exceptId = null): void
    {
        $parameters = [$name, $storeId];
        $condition = '`name` = ? AND `storeId` = ?';

        if ($exceptId !== null) {
            $condition .= ' AND `id` != ?';
            $parameters[] = $exceptId;
        }

        $taken = $this->connection->fetchOne(
            sprintf(
                'SELECT `id` FROM `%s` WHERE %s LIMIT 1 FOR UPDATE',
                SubGroupConfigDao::TABLE_NAME_SUBGROUPS,
                $condition
            ),
            $parameters
        );

        if ($taken === false) {
            return;
        }

        throw $this->duplicateName($name, $storeId);
    }

    /**
     * Which pair the schema refused is read back rather than taken from the driver's message, because
     * the table carries a unique index on the identifier as well and that collision is not a collision
     * of the name. A re-read that itself fails leaves the save's own answer standing.
     */
    private function nameCollisionOrSavingFailed(
        UniqueConstraintViolationException $e,
        string $name,
        int $storeId,
        ?int $id,
    ): AbstractApiException {
        try {
            $this->assertNameIsFree($name, $storeId, $id);
        } catch (InvalidArgumentException) {
            return $this->duplicateName($name, $storeId, $e);
        } catch (Throwable) {
            // the re-read only names the collision; it is not an answer of its own
        }

        return new ElementSavingFailedException($id, $e->getMessage(), $e);
    }

    private function duplicateName(string $name, int $storeId, ?Throwable $previous = null): InvalidArgumentException
    {
        return new InvalidArgumentException(
            sprintf('Sub group with the name "%s" already exists in store %d', $name, $storeId),
            $previous
        );
    }

    private function applySearchCondition(Listing $listing, FilterParameter $parameters): void
    {
        $searchFilter = $parameters->getSimpleColumnFilterByType(FilterType::SEARCH->value);
        if (!$searchFilter) {
            return;
        }

        $this->searchHelperService->applySearchTermFilter($listing, $searchFilter->getFilterValue());
    }
}
