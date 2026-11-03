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
use function sprintf;

/**
 * @internal
 */
final readonly class SubGroupRepository implements SubGroupRepositoryInterface
{
    private const string NOT_FOUND_TYPE = 'sub group configuration';

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
        // `:scopeStoreId`, not `:storeId`: a client supplied equals filter on `storeId` builds the
        // very condition string the route binding would, and Pimcore keys its conditions by that
        // string - an identical one replaces the binding instead of being added next to it. Under
        // its own name the two conditions stand side by side, so a filter can only narrow the store.
        $listing->addConditionParam('`storeId` = :scopeStoreId', ['scopeStoreId' => $storeId]);

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
        $usedBy = $this->getGroupNamesUsing($config->getStoreId(), $config->getIdentifier());

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
    }

    /**
     * The groups of the store whose key assignments carry the identifier. Scoped to the store
     * because the identifier is unique per store, not globally.
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
                    . ' WHERE `g`.`storeId` = ? AND `r`.`subGroup` = ?',
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
