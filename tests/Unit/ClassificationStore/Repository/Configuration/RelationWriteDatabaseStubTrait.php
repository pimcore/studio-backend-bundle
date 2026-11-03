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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Repository\Configuration;

use Doctrine\DBAL\Connection;
use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelationCache;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\KernelInterface;
use function array_shift;
use function str_contains;
use function trim;

/**
 * The repository writes through `KeyGroupRelation`, which resolves its connection through `Pimcore\Db`
 * and cannot be injected. This stands in for that connection, so a test can call the public entry point
 * and assert on the row that reaches the database - which is where "an absent property is not written"
 * and "the resolved sub group identifier is written, not the submitted one" are observable at all.
 *
 * A successful upsert is applied to the rows the listing loads, so the read-back the repository ends
 * with answers with what the write left rather than with the seeded fixture. Without that, a test can
 * only assert on the outgoing write and a response reporting a stale value passes unnoticed.
 *
 * There is no database in the unit suite, so the column list the write path reads is pre-seeded into the
 * runtime cache the way a first read would leave it, which keeps `SHOW COLUMNS` out of the stub.
 *
 * @internal
 */
trait RelationWriteDatabaseStubTrait
{
    private const string CONNECTION_SERVICE_ID = 'doctrine.dbal.default_connection';

    private const array RELATION_COLUMNS = [
        'groupId',
        'keyId',
        'sorter',
        'mandatory',
        'metadata',
        'definitionOverride',
        'subGroup',
    ];

    /**
     * @var list<array<string, mixed>>
     */
    private array $writtenRows = [];

    /**
     * The rows the relation listing loads. Held here rather than captured by the stub so that a
     * competing writer can be made to land between the lock and the read that follows it.
     *
     * @var list<array<string, mixed>>
     */
    private array $storedRows = [];

    /**
     * The locking reads the write path issued, in order, so a test can assert what was held and when.
     *
     * @var list<string>
     */
    private array $lockingReads = [];

    /**
     * The transaction boundaries the write path crossed, in order.
     *
     * @var list<string>
     */
    private array $transactionSteps = [];

    private bool $inTransaction = false;

    /**
     * @var (callable(): list<array<string, mixed>>)|null
     */
    private $competingWriteOnRelationLock = null;

    /**
     * The key side columns a newly inserted relation joins to, null when the pair names no key
     * configuration. The relation listing inner joins the key row
     * ({@see \Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing\Dao::getWhere()}
     * puts `relations.keyId = keys.id` in its WHERE), so an inserted relation becomes readable only
     * once there is a key row for it - which is why a create has to say so rather than have it assumed.
     *
     * @var array<string, mixed>|null
     */
    private ?array $keyColumnsForNewAssignments = null;

    private ?Connection $stubbedConnection = null;

    private ?KernelInterface $kernelBeforeStub = null;

    private bool $kernelStubbed = false;

    /**
     * @param list<array<string, mixed>> $rows the rows the relation listing loads
     * @param list<mixed> $singleValues answers for the reads the sub group resolution makes, in order
     */
    private function stubDatabase(array $rows, array $singleValues = []): void
    {
        $this->storedRows = $rows;

        $connection = $this->makeEmpty(Connection::class, [
            'quoteIdentifier' => static fn (string $identifier): string => '`' . $identifier . '`',
            'fetchAllAssociative' => fn (): array => $this->storedRows,
            // by reference, so the answers are consumed in order across the calls of one write
            'fetchOne' => static function () use (&$singleValues): mixed {
                return array_shift($singleValues);
            },
            // the group lock the repository fronts its write with; the value is never used, so
            // standing in for the lock is all this has to do - besides recording that it was taken
            'fetchFirstColumn' => function (string $query): array {
                $this->lockingReads[] = $query;
                $this->letACompetingWriterLand($query);

                return [];
            },
            // the assignment lock, which also answers with the two columns the write re-supplies
            'fetchAssociative' => function (string $query, array $params = []): array|false {
                $this->lockingReads[] = $query;
                $this->letACompetingWriterLand($query);

                return $this->findStoredRelation($params[0] ?? null, $params[1] ?? null);
            },
            // tracked rather than answered with a constant, so that core's own write joins the
            // transaction this one opens instead of opening a second one of its own
            'isTransactionActive' => fn (): bool => $this->inTransaction,
            'beginTransaction' => function (): bool {
                $this->inTransaction = true;
                $this->transactionSteps[] = 'begin';

                return true;
            },
            'commit' => function (): bool {
                $this->inTransaction = false;
                $this->transactionSteps[] = 'commit';

                return true;
            },
            'rollBack' => function (): bool {
                $this->inTransaction = false;
                $this->transactionSteps[] = 'rollback';

                return true;
            },
            'insert' => function (string $table, array $data): int {
                $this->writtenRows[] = $data;
                $this->applyWrite($this->unquoteColumns($data));

                return 1;
            },
            'lastInsertId' => static fn (): string => '0',
        ]);

        $this->stubbedConnection = $connection;

        $container = new Container();
        $container->set(self::CONNECTION_SERVICE_ID, $connection);
        $container->set(KeyGroupRelationCache::class, new KeyGroupRelationCache());

        $this->kernelBeforeStub = Pimcore::getKernel();
        $this->kernelStubbed = true;
        Pimcore::setKernel($this->makeEmpty(KernelInterface::class, ['getContainer' => $container]));

        RuntimeCache::set($this->getColumnCacheKey(), [
            'columns' => self::RELATION_COLUMNS,
            'primaryKeyColumns' => ['groupId', 'keyId'],
        ]);
    }

    private function getStubbedConnection(): Connection
    {
        return $this->stubbedConnection ?? $this->makeEmpty(Connection::class);
    }

    /**
     * Stands in for a second request that commits its own update of the same assignment while this one
     * waits for the row - the interleaving the lock exists to rule out. The replacement row set is the
     * state that writer left, and it is installed when the relation lock is granted, so a read taken
     * before the lock would miss it and a read taken after it cannot.
     *
     * @param callable(): list<array<string, mixed>> $rowsItLeaves
     */
    private function letACompetingWriterCommitOnTheRelationLock(callable $rowsItLeaves): void
    {
        $this->competingWriteOnRelationLock = $rowsItLeaves;
    }

    /**
     * Makes the key id of the payload name a key configuration, so a relation this write inserts is
     * readable afterwards. Without it an inserted relation joins to nothing, which is the pair the
     * read-back refuses.
     *
     * @param array<string, mixed> $keyColumns the key side columns the relation listing selects
     */
    private function letTheKeyExistForNewAssignments(array $keyColumns): void
    {
        $this->keyColumnsForNewAssignments = $keyColumns;
    }

    /**
     * Applies a successful upsert to the rows the listing loads. A column the write left out keeps its
     * stored value, which is what leaving it out of the statement means, and the values are taken as
     * the write handed them over - already encoded the way the stored row carries them.
     *
     * @param array<string, mixed> $written
     */
    private function applyWrite(array $written): void
    {
        foreach ($this->storedRows as $index => $stored) {
            if (!$this->isTheSamePair($stored, $written)) {
                continue;
            }

            $this->storedRows[$index] = $written + $stored;

            return;
        }

        if ($this->keyColumnsForNewAssignments === null) {
            return;
        }

        $this->storedRows[] = $written + $this->keyColumnsForNewAssignments;
    }

    /**
     * The two columns the assignment lock answers with, or false for a pair with no row yet.
     *
     * @return array<string, mixed>|false
     */
    private function findStoredRelation(mixed $keyId, mixed $groupId): array|false
    {
        $addressed = ['keyId' => $keyId, 'groupId' => $groupId];

        foreach ($this->storedRows as $row) {
            if ($this->isTheSamePair($row, $addressed)) {
                return ['sorter' => $row['sorter'] ?? null, 'mandatory' => $row['mandatory'] ?? null];
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $other
     */
    private function isTheSamePair(array $row, array $other): bool
    {
        return (int) ($row['keyId'] ?? 0) === (int) ($other['keyId'] ?? 0)
            && (int) ($row['groupId'] ?? 0) === (int) ($other['groupId'] ?? 0);
    }

    private function letACompetingWriterLand(string $query): void
    {
        $competingWrite = $this->competingWriteOnRelationLock;
        if ($competingWrite === null || !str_contains($query, Dao::TABLE_NAME_RELATIONS)) {
            return;
        }

        // once, so the writer commits rather than being replayed on every lock the path takes
        $this->competingWriteOnRelationLock = null;
        $this->storedRows = $competingWrite();
    }

    /**
     * The locking reads the write path issued, in order.
     *
     * @return list<string>
     */
    private function getLockingReads(): array
    {
        return $this->lockingReads;
    }

    /**
     * The transaction boundaries the write path crossed, in order.
     *
     * @return list<string>
     */
    private function getTransactionSteps(): array
    {
        return $this->transactionSteps;
    }

    /**
     * The row the write path handed to the database, with the quoting of its column names undone.
     *
     * @return array<string, mixed>
     */
    private function getWrittenRow(): array
    {
        $this->assertCount(1, $this->writtenRows, 'Expected the entry point to write a single row.');

        return $this->unquoteColumns($this->writtenRows[0]);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function unquoteColumns(array $row): array
    {
        $unquoted = [];
        foreach ($row as $column => $value) {
            $unquoted[trim($column, '`')] = $value;
        }

        return $unquoted;
    }

    private function assertNothingWasWritten(): void
    {
        $this->assertSame([], $this->writtenRows, 'Expected the refusal to leave the row alone.');
    }

    protected function _after(): void
    {
        $this->writtenRows = [];
        $this->storedRows = [];
        $this->lockingReads = [];
        $this->transactionSteps = [];
        $this->inTransaction = false;
        $this->competingWriteOnRelationLock = null;
        $this->keyColumnsForNewAssignments = null;
        $this->stubbedConnection = null;

        if (!$this->kernelStubbed) {
            return;
        }

        RuntimeCache::getInstance()->offsetUnset($this->getColumnCacheKey());

        // The suite is shuffled and Pimcore::setKernel() does not take null, so put the previous
        // kernel back the only way there is.
        (new ReflectionProperty(Pimcore::class, 'kernel'))->setValue(null, $this->kernelBeforeStub);
        $this->kernelStubbed = false;
    }

    private function getColumnCacheKey(): string
    {
        return AbstractDao::CACHEKEY . Dao::TABLE_NAME_RELATIONS;
    }
}
