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

use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Pimcore;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao as KeyGroupRelationDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelationCache;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Dao as SubGroupConfigDao;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\KernelInterface;
use function sprintf;
use function str_contains;

/**
 * The repository builds its listings with `new Listing()` and saves through the model, so neither can
 * be injected. This stands in for the connection both of them resolve through `Pimcore\Db`, and is
 * the repository's own `Connection` dependency as well, which lets a test drive the two reads the
 * delete decision rests on - the vocabulary row and the assignments carrying it - and then assert on
 * the writes that did or did not reach the database.
 *
 * @internal
 */
final class SubGroupRepositoryTest extends Unit
{
    private const string CONNECTION_SERVICE_ID = 'doctrine.dbal.default_connection';

    private const int STORE_ID = 1;

    private const int SUB_GROUP_ID = 21;

    private const string SUB_GROUP_NAME = 'Dimensions';

    private const string OTHER_NAME = 'Logistics';

    private const string RENAMED = 'Measurements';

    private const string DESCRIPTION = 'Physical dimensions';

    private const string IDENTIFIER = '01JBQ8X9Z0ABCDEFGHJKMNPQRS';

    /**
     * @var list<string>
     */
    private array $writes = [];

    /**
     * @var list<string>
     */
    private array $reads = [];

    private ?KernelInterface $kernelBeforeStub = null;

    private bool $kernelStubbed = false;

    private bool $inTransaction = false;

    public function testUpdateReturnsTheModifiedConfig(): void
    {
        $repository = $this->createRepository(subGroupRows: [$this->createRow()]);

        $config = $repository->update(self::SUB_GROUP_ID, self::RENAMED, self::DESCRIPTION);

        $this->assertSame(self::RENAMED, $config->getName());
        $this->assertSame(self::DESCRIPTION, $config->getDescription());
        $this->assertSame(self::STORE_ID, $config->getStoreId());
        // a rename never reassigns the link the assignments hold
        $this->assertSame(self::IDENTIFIER, $config->getIdentifier());
    }

    /**
     * The pairs are written only when the request supplied them: an omitted member never initializes
     * the model's column, a supplied map replaces it, and a supplied null clears it.
     */
    public function testUpdateWritesThePairsOnlyWhenTheyAreSupplied(): void
    {
        $repository = $this->createRepository(subGroupRows: [$this->createRow()]);

        $this->assertFalse(
            $repository->update(self::SUB_GROUP_ID, self::RENAMED, null)->isCustomMetadataInitialized()
        );

        $pairs = ['indexed' => 'yes', 'title.de' => 'Maße'];
        $this->assertSame(
            $pairs,
            $repository->update(self::SUB_GROUP_ID, self::RENAMED, null, true, $pairs)->getCustomMetadata()
        );

        $cleared = $repository->update(self::SUB_GROUP_ID, self::RENAMED, null, true, null);
        $this->assertTrue($cleared->isCustomMetadataInitialized());
        $this->assertNull($cleared->getCustomMetadata());

        $this->assertNull(
            $repository->update(self::SUB_GROUP_ID, self::RENAMED, null, true, [])->getCustomMetadata()
        );
    }

    public function testUpdateOfAnUnknownIdIsNotFound(): void
    {
        $repository = $this->createRepository(subGroupRows: []);

        try {
            $repository->update(404, self::RENAMED, null);
            $this->fail('Expected the unknown sub group to be refused.');
        } catch (NotFoundException $e) {
            $this->assertSame(404, $e->getStatusCode());
            $this->assertStringContainsStringIgnoringCase('sub group configuration', $e->getMessage());
        }
    }

    public function testDeleteOfAnUnknownIdIsNotFound(): void
    {
        $repository = $this->createRepository(subGroupRows: []);

        try {
            $repository->delete(404);
            $this->fail('Expected the unknown sub group to be refused.');
        } catch (NotFoundException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }

    public function testCreateRefusesADuplicateNameInTheSameStore(): void
    {
        $repository = $this->createRepository(duplicateRows: [$this->createRow()]);

        try {
            $repository->create(self::SUB_GROUP_NAME, self::STORE_ID);
            $this->fail('Expected the duplicate name to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertStringContainsString(self::SUB_GROUP_NAME, $e->getMessage());
            $this->assertStringContainsString('store 1', $e->getMessage());
        }

        $this->assertSame([], $this->writes, 'A refused create must not write.');
    }

    public function testUpdateRefusesARenameOntoAnExistingName(): void
    {
        $repository = $this->createRepository(
            subGroupRows: [$this->createRow()],
            duplicateRows: [$this->createRow(id: 22, name: self::OTHER_NAME)]
        );

        try {
            $repository->update(self::SUB_GROUP_ID, self::OTHER_NAME, null);
            $this->fail('Expected the rename onto an existing name to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertStringContainsString(self::OTHER_NAME, $e->getMessage());
            $this->assertStringContainsString('store 1', $e->getMessage());
        }
    }

    /**
     * The duplicate probe is scoped to the store, so the name is free again in a second one.
     */
    public function testCreateAcceptsTheSameNameInAnotherStore(): void
    {
        $repository = $this->createRepository(duplicateRows: [], savingANewRow: true);

        $config = $repository->create(self::SUB_GROUP_NAME, 2);

        $this->assertSame(self::SUB_GROUP_NAME, $config->getName());
        $this->assertSame(2, $config->getStoreId());
        $this->assertTrue(
            $this->hasRead('`name` =') && $this->hasRead('`storeId` ='),
            'The duplicate probe has to be scoped by name and store.'
        );
    }

    /**
     * Renaming an entry to the name it already carries is not a duplicate: the probe excludes the
     * row being updated, or saving an unchanged name would be refused.
     */
    public function testUpdateAcceptsTheNameTheSubGroupAlreadyCarries(): void
    {
        $repository = $this->createRepository(subGroupRows: [$this->createRow()]);

        $config = $repository->update(self::SUB_GROUP_ID, self::SUB_GROUP_NAME, 'Changed description');

        $this->assertSame(self::SUB_GROUP_NAME, $config->getName());
        $this->assertSame('Changed description', $config->getDescription());
        $this->assertTrue($this->hasRead('`id` !='), 'The probe has to exclude the row being updated.');
    }

    public function testDeleteRemovesAnUnusedSubGroup(): void
    {
        $repository = $this->createRepository(
            subGroupRows: [$this->createRow()],
            usedByGroups: []
        );

        $repository->delete(self::SUB_GROUP_ID);

        $this->assertTrue($this->hasWrite('DELETE'), 'An unused sub group has to be deleted.');

        // the other direction of the transition: the row is gone, so a re-read reports it missing
        $afterDelete = $this->createRepository(subGroupRows: []);

        $this->expectException(NotFoundException::class);

        $afterDelete->getById(self::SUB_GROUP_ID);
    }

    public function testDeleteIsRefusedWhileAssignmentsCarryTheSubGroup(): void
    {
        $repository = $this->createRepository(
            subGroupRows: [$this->createRow()],
            usedByGroups: ['Technical', self::OTHER_NAME]
        );

        try {
            $repository->delete(self::SUB_GROUP_ID);
            $this->fail('Expected the sub group in use to be refused.');
        } catch (ConflictException $e) {
            $this->assertSame(409, $e->getStatusCode());
            $this->assertStringContainsString('Technical', $e->getMessage());
            $this->assertStringContainsString(self::OTHER_NAME, $e->getMessage());
        }

        // every relation row and the vocabulary row are left as they were
        $this->assertSame([], $this->writes, 'A refused delete must not write.');
        $this->assertFalse($this->inTransaction, 'A refused delete has to leave no transaction open.');
    }

    /**
     * The interleaving the probe used to lose: an assignment commits after the usage probe would
     * have run as a read of its own, and before the delete. Probing from inside the delete's own
     * transaction, behind the lock an assignment writer has to take, is what turns that into the
     * 409 instead of into a tag cleared without one.
     */
    public function testDeleteIsRefusedWhenTheAssignmentArrivesWhileTheLocksAreTaken(): void
    {
        $repository = $this->createRepository(
            subGroupRows: [$this->createRow()],
            tagCommittedOnceLocked: true
        );

        try {
            $repository->delete(self::SUB_GROUP_ID);
            $this->fail('Expected the sub group tagged under the lock to be refused.');
        } catch (ConflictException $e) {
            $this->assertSame(409, $e->getStatusCode());
            $this->assertStringContainsString(self::OTHER_NAME, $e->getMessage());
        }

        $this->assertSame([], $this->writes, 'The arriving assignment must not be cleared.');
        $this->assertFalse($this->inTransaction, 'A refused delete has to leave no transaction open.');
    }

    /**
     * Groups, then the vocabulary row, then the relations under them - the order every writer of
     * this family takes the three tables in. Taking them the other way round closes a deadlock
     * cycle against {@see KeyGroupRelationDao::update()} and core's own delete.
     */
    public function testDeleteTakesItsLocksInTheOrderTheOtherWritersDo(): void
    {
        $repository = $this->createRepository(
            subGroupRows: [$this->createRow()],
            usedByGroups: []
        );

        $repository->delete(self::SUB_GROUP_ID);

        $groups = $this->indexOfRead(
            GroupConfigDao::TABLE_NAME_GROUPS . '` WHERE `storeId` = ? ORDER BY `id` FOR UPDATE'
        );
        $subGroup = $this->indexOfRead(
            'SELECT `storeId`, `identifier` FROM `' . SubGroupConfigDao::TABLE_NAME_SUBGROUPS
        );
        $relations = $this->indexOfRead(KeyGroupRelationDao::TABLE_NAME_RELATIONS);

        $this->assertNotNull($groups, 'The groups of the store have to be locked first.');
        $this->assertNotNull($subGroup, 'The vocabulary row has to be locked as a current read.');
        $this->assertNotNull($relations, 'The assignments have to be probed.');
        $this->assertLessThan($subGroup, $groups, 'The groups have to be locked before the entry.');
        $this->assertLessThan($relations, $subGroup, 'The entry has to be locked before the relations.');
    }

    /**
     * Both reads the decision rests on read as of now rather than from the transaction's snapshot,
     * which is what lets them see an assignment a competing writer has already committed.
     */
    public function testDeleteProbesWithLockingReads(): void
    {
        $repository = $this->createRepository(
            subGroupRows: [$this->createRow()],
            usedByGroups: ['Technical']
        );

        try {
            $repository->delete(self::SUB_GROUP_ID);
            $this->fail('Expected the sub group in use to be refused.');
        } catch (ConflictException) {
            // the refusal itself is asserted above; this case is about how it was decided
        }

        foreach ([SubGroupConfigDao::TABLE_NAME_SUBGROUPS, KeyGroupRelationDao::TABLE_NAME_RELATIONS] as $table) {
            $this->assertTrue(
                $this->hasLockingRead($table),
                sprintf('The read of `%s` the delete decides on has to be a locking one.', $table)
            );
        }
    }

    /**
     * @param list<array<string, mixed>> $subGroupRows
     * @param list<array<string, mixed>> $duplicateRows
     * @param list<string> $usedByGroups
     */
    private function createRepository(
        array $subGroupRows = [],
        array $duplicateRows = [],
        array $usedByGroups = [],
        bool $savingANewRow = false,
        bool $tagCommittedOnceLocked = false,
    ): SubGroupRepository {
        // a create writes into the placeholder row it just inserted, which carries neither yet
        $storedStoreId = $savingANewRow ? null : self::STORE_ID;
        $storedIdentifier = $savingANewRow ? null : self::IDENTIFIER;

        $connection = $this->makeEmpty(Connection::class, [
            'quoteIdentifier' => static fn (string $identifier): string => '`' . $identifier . '`',
            'quote' => static fn (string $value): string => "'" . $value . "'",
            // the repository runs its probe and its write as one unit of work, and core's own writes
            // join an active transaction instead of opening a second one, so the stub has to answer
            // this the way the connection would
            'isTransactionActive' => fn (): bool => $this->inTransaction,
            'beginTransaction' => function (): void {
                $this->inTransaction = true;
            },
            'commit' => function (): void {
                $this->inTransaction = false;
            },
            'rollBack' => function (): void {
                $this->inTransaction = false;
            },
            'fetchAllAssociative' => function (string $query) use ($subGroupRows): array {
                $this->reads[] = $query;

                return $subGroupRows;
            },
            // the usage probe, and the group locks the repository and core take ahead of it
            'fetchFirstColumn' => function (string $query) use ($usedByGroups, $tagCommittedOnceLocked): array {
                $this->reads[] = $query;

                // a group lock reports ids; only the join over the relations reports group names
                if (!str_contains($query, KeyGroupRelationDao::TABLE_NAME_RELATIONS)) {
                    return [];
                }

                // The interleaving: the assignment is committed while the locks are being taken,
                // so a read before the transaction reports the entry unused and only a read from
                // inside it sees the tag. A probe taken outside the delete's transaction - which is
                // what this used to be - would answer the first way and let the delete through.
                if ($tagCommittedOnceLocked) {
                    return $this->inTransaction ? [self::OTHER_NAME] : [];
                }

                return $usedByGroups;
            },
            'fetchAssociative' => function (string $query): array {
                $this->reads[] = $query;

                return [
                    'storeId' => self::STORE_ID,
                    'identifier' => self::IDENTIFIER,
                ];
            },
            'fetchOne' => function (
                string $query
            ) use ($duplicateRows, $storedStoreId, $storedIdentifier): bool|int|string|null {
                // the duplicate probe and the lock it takes on the store first, both locking reads
                // on the way into a write rather than listings
                if (str_contains($query, '`name` =') || str_contains($query, 'classificationstore_stores')) {
                    $this->reads[] = $query;
                }

                return match (true) {
                    // no row for the name, the way the connection answers a probe that found it free
                    str_contains($query, '`name` =') => $duplicateRows === []
                        ? false
                        : (int) $duplicateRows[0]['id'],
                    // core reads the stored identifier back to enforce that it is written once
                    str_contains($query, 'SELECT `identifier`') => $storedIdentifier,
                    str_contains($query, 'SELECT `storeId`') => $storedStoreId,
                    // the identifier is unique per store and this one is not taken by another row
                    str_contains($query, 'SELECT id FROM') => false,
                    default => null,
                };
            },
            'executeStatement' => function (string $query): int {
                $this->writes[] = $query;

                return 1;
            },
            'insert' => function (string $table): int {
                $this->writes[] = 'INSERT ' . $table;

                return 1;
            },
            'update' => function (string $table): int {
                $this->writes[] = 'UPDATE ' . $table;

                return 1;
            },
            'delete' => function (string $table): int {
                $this->writes[] = 'DELETE ' . $table;

                return 1;
            },
            'lastInsertId' => static fn (): string => (string) self::SUB_GROUP_ID,
        ]);

        // The model's DAO asks for the table's columns before writing. Registering them in the
        // runtime cache keeps that lookup away from the core cache handler, which the stub
        // container has no reason to carry.
        RuntimeCache::set(
            AbstractDao::CACHEKEY . SubGroupConfigDao::TABLE_NAME_SUBGROUPS,
            [
                'columns' => [
                    'id', 'storeId', 'name', 'description', 'identifier', 'creationDate', 'modificationDate',
                ],
                'primaryKeyColumns' => ['id'],
            ]
        );

        $container = new Container();
        $container->set(self::CONNECTION_SERVICE_ID, $connection);
        // final and constructor-less, so core's delete gets the real one to drop its groups from
        $container->set(KeyGroupRelationCache::class, new KeyGroupRelationCache());

        // A test may build several repositories; only the first sees the real kernel to put back.
        if (!$this->kernelStubbed) {
            $this->kernelBeforeStub = Pimcore::getKernel();
            $this->kernelStubbed = true;
        }
        Pimcore::setKernel($this->makeEmpty(KernelInterface::class, ['getContainer' => $container]));

        return new SubGroupRepository(
            $this->makeEmpty(ListingFilterInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class),
            $connection
        );
    }

    private function hasRead(string $needle): bool
    {
        return $this->indexOfRead($needle) !== null;
    }

    /**
     * Where in the sequence of reads the first one matching appeared, so that a test can assert the
     * order two locks were taken in rather than only that both were.
     */
    private function indexOfRead(string $needle): ?int
    {
        foreach ($this->reads as $index => $query) {
            if (str_contains($query, $needle)) {
                return $index;
            }
        }

        return null;
    }

    private function hasLockingRead(string $table): bool
    {
        foreach ($this->reads as $query) {
            if (str_contains($query, $table) && str_contains($query, 'FOR UPDATE')) {
                return true;
            }
        }

        return false;
    }

    private function hasWrite(string $needle): bool
    {
        foreach ($this->writes as $query) {
            if (str_contains($query, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function createRow(int $id = self::SUB_GROUP_ID, string $name = self::SUB_GROUP_NAME): array
    {
        return [
            'id' => $id,
            'storeId' => self::STORE_ID,
            'name' => $name,
            'description' => self::DESCRIPTION,
            'identifier' => self::IDENTIFIER,
            'creationDate' => 1734567890,
            'modificationDate' => 1734567891,
        ];
    }

    protected function _after(): void
    {
        $this->writes = [];
        $this->reads = [];
        $this->inTransaction = false;

        if (!$this->kernelStubbed) {
            return;
        }

        // The suite is shuffled and Pimcore::setKernel() does not take null, so put the previous
        // kernel back the only way there is.
        (new ReflectionProperty(Pimcore::class, 'kernel'))->setValue(null, $this->kernelBeforeStub);
        $this->kernelStubbed = false;
    }
}
