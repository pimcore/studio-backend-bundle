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
use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao as KeyGroupRelationDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Dao as SubGroupConfigDao;
use Pimcore\Model\Exception\InvalidConfigException;
use Throwable;
use function sprintf;

/**
 * @internal
 */
final readonly class KeyGroupRelationRepository implements KeyGroupRelationRepositoryInterface
{
    public function __construct(
        private ListingFilterInterface $listingFilter,
        private Connection $connection,
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
    public function createOrUpdate(int $keyId, int $groupId, int $sorter, bool $mandatory): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId($groupId);
        $relation->setSorter($sorter);
        $relation->setMandatory($mandatory);

        // The write and the read-back are one unit for the same reason the update below is: the
        // instance carries only the four properties the payload set, so the three the write leaves
        // alone are reported from the stored row - and a competing writer committing between the two
        // would have the response name values this request never wrote.
        return $this->writeHoldingTheAssignment(
            $keyId,
            $groupId,
            function () use ($relation, $keyId, $groupId): KeyGroupRelation {
                $relation->save();

                return $this->getByKeyAndGroup($keyId, $groupId);
            },
            static fn (Exception $e): AbstractApiException => new ElementSavingFailedException(
                null,
                $e->getMessage(),
                $e
            )
        );
    }

    /**
     * {@inheritdoc}
     *
     * Core's upsert writes every column the instance carries, not only the ones the payload named
     * ({@see KeyGroupRelationDao::update()}), so the properties this payload omits are re-written
     * from whatever the read a moment earlier saw. Read and write therefore have to be one unit:
     * with them apart, two PUTs that load the same row before either saves both write their own
     * view of all five columns, and the one that commits second silently restores the other's
     * omitted columns to the values it read - a sorter-only PUT undoing a metadata-only PUT.
     *
     * What makes them one unit is the assignment row itself, held before it is read and until the
     * save commits, so a competing writer either committed before this read - and is read by it -
     * or waits for this transaction and then reads what it left.
     */
    public function update(int $keyId, int $groupId, KeyGroupRelationUpdate $parameters): KeyGroupRelation
    {
        return $this->writeHoldingTheAssignment(
            $keyId,
            $groupId,
            function () use ($keyId, $groupId, $parameters): KeyGroupRelation {
                $relation = $this->getByKeyAndGroup($keyId, $groupId);
                $this->applyUpdate($relation, $parameters);
                $relation->save();

                return $relation;
            },
            fn (Exception $e): AbstractApiException => $this->mapWriteFailure($e)
        );
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
     * Runs a write against the addressed assignment as one unit of work, with the row held from before
     * the first read of it until the unit commits.
     *
     * An enclosing transaction stays the caller's to finish, the way core's own writes join one
     * ({@see \Pimcore\Model\DataObject\Classificationstore\Traits\AtomicWriteTrait}) - and core's write
     * joins this one, so the save inside neither opens nor commits a transaction of its own.
     *
     * An answer the API already has a status for passes through; anything else is mapped by the caller,
     * which is what keeps the 404 of an unknown pair and the 422 of a refused sub group from arriving as
     * the 500 a failed save is.
     *
     * @template T
     *
     * @param callable(): T $write
     * @param callable(Exception): AbstractApiException $mapFailure
     *
     * @return T
     *
     * @throws AbstractApiException
     */
    private function writeHoldingTheAssignment(int $keyId, int $groupId, callable $write, callable $mapFailure): mixed
    {
        $ownTransaction = !$this->connection->isTransactionActive();
        if ($ownTransaction) {
            $this->connection->beginTransaction();
        }

        try {
            $this->holdGroup($groupId);
            $this->holdRelation($keyId, $groupId);

            $result = $write();
        } catch (Throwable $e) {
            if ($ownTransaction && $this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw match (true) {
                $e instanceof AbstractApiException => $e,
                $e instanceof Exception => $mapFailure($e),
                default => $e,
            };
        }

        if ($ownTransaction) {
            $this->connection->commit();
        }

        return $result;
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
     * Holds the addressed assignment row for the rest of the transaction. It is a lock rather than a
     * read - {@see self::getByKeyAndGroup()} does the reading, and an absent row is its 404 - and it is
     * taken before that read so that the read answers with what the writer this transaction waited for
     * left behind: this and {@see self::holdGroup()} are locking reads, which take no consistent-read
     * view of their own, so the view the plain read after them establishes is the transaction's first
     * and is created once the lock is already held - and the row cannot move while it is.
     *
     * That ordering is what a transaction opened here gives. An enclosing one brought by a caller may
     * already carry a view older than this lock, and a read under it would answer from that view; there
     * is no such caller today, and one would have to read the assignment under its own lock instead.
     *
     * A pair with no row yet locks the gap it would be inserted into, which is what makes two creates of
     * the same pair, or a create racing this update, run one after the other rather than together.
     *
     * The row this holds is the row core's own upsert writes ({@see KeyGroupRelationDao::update()}), so
     * every write of this assignment contends here, whichever endpoint it arrived through.
     *
     * @throws DBALException
     */
    private function holdRelation(int $keyId, int $groupId): void
    {
        $this->connection->fetchFirstColumn(
            sprintf(
                'SELECT `keyId` FROM `%s` WHERE `keyId` = ? AND `groupId` = ? FOR UPDATE',
                KeyGroupRelationDao::TABLE_NAME_RELATIONS
            ),
            [$keyId, $groupId]
        );
    }

    /**
     * A refusal by the classification store is the fault of the request, so it surfaces as 422 with the
     * message of the refusal rather than as the 500 a save failure produces.
     */
    private function mapWriteFailure(Exception $e): AbstractApiException
    {
        if ($e instanceof InvalidConfigException) {
            return new InvalidArgumentException($e->getMessage(), $e);
        }

        return new ElementSavingFailedException(null, $e->getMessage(), $e);
    }

    /**
     * @throws InvalidConfigException
     */
    private function applyUpdate(KeyGroupRelation $relation, KeyGroupRelationUpdate $parameters): void
    {
        if ($parameters->hasSorter()) {
            $relation->setSorter($parameters->getSorter());
        }

        if ($parameters->hasMandatory()) {
            $relation->setMandatory($parameters->isMandatory());
        }

        if ($parameters->hasDefinitionOverride()) {
            $relation->setDefinitionOverride($parameters->getDefinitionOverride());
        }

        if ($parameters->hasSubGroup()) {
            $relation->setSubGroup($parameters->getSubGroup());
        }

        if ($parameters->hasMetadata()) {
            $relation->setMetadata($parameters->getMetadata());
        }
    }

    /**
     * The addressed row is loaded here rather than taken from the request memo of the key relation
     * resolver, which hands out clones of what it saw earlier in the request and would load every
     * relation of the group to answer for one pair.
     *
     * It is a plain read, and the relation listing has no locking form to offer; what makes it answer
     * with the current row rather than with a snapshot is that every caller of it inside a write runs
     * under {@see self::holdRelation()}, whose lock is taken first and held throughout.
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
            throw new NotFoundException('KeyGroupRelation', $keyId);
        }

        return $relations[0];
    }
}
