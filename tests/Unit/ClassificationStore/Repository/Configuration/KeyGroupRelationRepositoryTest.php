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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao as KeyGroupRelationDao;
use function str_contains;

/**
 * @internal
 */
final class KeyGroupRelationRepositoryTest extends Unit
{
    use RelationWriteDatabaseStubTrait;

    private const int KEY_ID = 11;

    private const int GROUP_ID = 7;

    private const int STORE_ID = 1;

    private const string STORED_SUB_GROUP = 'Dimensions';

    private const string STORED_DEFINITION_OVERRIDE_JSON = '{"defaultUnit":"kg"}';

    private const string STORED_METADATA_JSON = '{"source":"erp"}';

    private const array STORED_DEFINITION_OVERRIDE = ['defaultUnit' => 'kg'];

    private const array STORED_METADATA = ['source' => 'erp'];

    private const string COMPETING_METADATA_JSON = '{"source":"pim"}';

    private const array COMPETING_METADATA = ['source' => 'pim'];

    private const string COLUMN_SORTER = 'sorter';

    private const string COLUMN_MANDATORY = 'mandatory';

    private const string COLUMN_DEFINITION_OVERRIDE = 'definitionOverride';

    private const string COLUMN_SUB_GROUP = 'subGroup';

    private const string COLUMN_METADATA = 'metadata';

    public function testUpdateKeepsThePropertiesThePayloadDoesNotCarry(): void
    {
        $this->stubDatabase([$this->createRow()], [self::STORE_ID, self::STORED_SUB_GROUP]);

        $relation = $this->createRepository()->update(
            self::KEY_ID,
            self::GROUP_ID,
            new KeyGroupRelationUpdate(sorter: 5)
        );

        $row = $this->getWrittenRow();

        $this->assertSame(5, $row[self::COLUMN_SORTER]);
        $this->assertSame(1, $row[self::COLUMN_MANDATORY]);
        $this->assertSame(self::STORED_DEFINITION_OVERRIDE_JSON, $row[self::COLUMN_DEFINITION_OVERRIDE]);
        $this->assertSame(self::STORED_SUB_GROUP, $row[self::COLUMN_SUB_GROUP]);
        $this->assertSame(self::STORED_METADATA_JSON, $row[self::COLUMN_METADATA]);
        $this->assertSame(self::STORED_DEFINITION_OVERRIDE, $relation->getDefinitionOverride());
        $this->assertSame(self::STORED_SUB_GROUP, $relation->getSubGroup());
        $this->assertSame(self::STORED_METADATA, $relation->getMetadata());
    }

    /**
     * @dataProvider nullableColumnProvider
     *
     * @param array<string, mixed> $payload
     */
    public function testUpdateClearsANullableColumnThePayloadSetsToNull(string $column, array $payload): void
    {
        $this->stubDatabase([$this->createRow()], [self::STORE_ID, self::STORED_SUB_GROUP]);

        $this->createRepository()->update(self::KEY_ID, self::GROUP_ID, new KeyGroupRelationUpdate(...$payload));

        $this->assertNull($this->getWrittenRow()[$column]);
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function nullableColumnProvider(): array
    {
        return [
            'definition override' => [
                self::COLUMN_DEFINITION_OVERRIDE,
                [self::COLUMN_DEFINITION_OVERRIDE => null],
            ],
            'sub group' => [
                self::COLUMN_SUB_GROUP,
                [self::COLUMN_SUB_GROUP => null],
            ],
            'metadata' => [
                self::COLUMN_METADATA,
                [self::COLUMN_METADATA => null],
            ],
        ];
    }

    public function testUpdateStoresTheSubGroupIdentifierTheStoreResolvedTo(): void
    {
        $this->stubDatabase([$this->createRow()], [self::STORE_ID, self::STORED_SUB_GROUP]);

        $relation = $this->createRepository()->update(
            self::KEY_ID,
            self::GROUP_ID,
            new KeyGroupRelationUpdate(subGroup: 'dimensions')
        );

        $this->assertSame(self::STORED_SUB_GROUP, $this->getWrittenRow()[self::COLUMN_SUB_GROUP]);
        $this->assertSame(self::STORED_SUB_GROUP, $relation->getSubGroup());
    }

    public function testUpdateTurnsAnUnknownSubGroupIntoAnUnprocessableContent(): void
    {
        $this->stubDatabase([$this->createRow()], [self::STORE_ID, false]);

        try {
            $this->createRepository()->update(
                self::KEY_ID,
                self::GROUP_ID,
                new KeyGroupRelationUpdate(subGroup: 'nope')
            );
            $this->fail('Expected the unknown sub group to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'The sub group "nope" does not exist in the store of group 7.',
                $e->getMessage()
            );
        }

        $this->assertNothingWasWritten();
    }

    public function testUpdateTurnsARefusedDefinitionOverrideIntoAnUnprocessableContent(): void
    {
        $this->stubDatabase([$this->createRow()]);

        try {
            $this->createRepository()->update(
                self::KEY_ID,
                self::GROUP_ID,
                new KeyGroupRelationUpdate(definitionOverride: ['fieldtype' => 'select'])
            );
            $this->fail('Expected the refused definition override to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('"fieldtype" cannot be overridden per key assignment.', $e->getMessage());
        }

        $this->assertNothingWasWritten();
    }

    public function testUpdateReportsAnUnknownPairAsNotFoundWithoutWriting(): void
    {
        $this->stubDatabase([]);

        try {
            $this->createRepository()->update(self::KEY_ID, self::GROUP_ID, new KeyGroupRelationUpdate(sorter: 5));
            $this->fail('Expected the unknown pair to be refused.');
        } catch (NotFoundException) {
            $this->assertNothingWasWritten();
        }
    }

    public function testCreateOrUpdateLeavesThePerAssignmentColumnsOfAnExistingRowAlone(): void
    {
        $this->stubDatabase([$this->createRow()]);

        $this->createRepository()->createOrUpdate(self::KEY_ID, self::GROUP_ID, 2, true);

        $row = $this->getWrittenRow();

        // absent from the write, so the three columns of an existing row keep what they hold
        $this->assertArrayNotHasKey(self::COLUMN_DEFINITION_OVERRIDE, $row);
        $this->assertArrayNotHasKey(self::COLUMN_SUB_GROUP, $row);
        $this->assertArrayNotHasKey(self::COLUMN_METADATA, $row);
        $this->assertSame(2, $row[self::COLUMN_SORTER]);
        $this->assertSame(1, $row[self::COLUMN_MANDATORY]);
    }

    /**
     * The instance the write path saves carries only the four properties the payload set, so the three
     * the write preserved are reported only because the stored row is read back afterwards.
     */
    public function testCreateOrUpdateReportsThePerAssignmentColumnsTheWritePreserved(): void
    {
        // the one row the relation listing loads, so the read-back after the write resolves to it
        $this->stubDatabase([$this->createRow()]);

        $relation = $this->createRepository()->createOrUpdate(self::KEY_ID, self::GROUP_ID, 2, true);

        $this->assertSame(self::STORED_DEFINITION_OVERRIDE, $relation->getDefinitionOverride());
        $this->assertSame(self::STORED_SUB_GROUP, $relation->getSubGroup());
        $this->assertSame(self::STORED_METADATA, $relation->getMetadata());
    }

    /**
     * The defect this pins: the row used to be read before the write opened its transaction, so two
     * PUTs that both read before either saved each wrote their own view of all five columns, and the
     * second to commit restored the first's omitted columns to what it had read.
     *
     * The competing writer here commits its metadata update while this request waits for the row, which
     * is the interleaving that used to lose it: the update that follows carries only a sorter, so the
     * metadata it writes back is the competitor's only if the row was read after the lock was granted.
     */
    public function testUpdateKeepsAnOmittedPropertyACompetingWriterCommittedFirst(): void
    {
        $this->stubDatabase([$this->createRow()], [self::STORE_ID, self::STORED_SUB_GROUP]);
        $this->letACompetingWriterCommitOnTheRelationLock(
            fn (): array => [[self::COLUMN_METADATA => self::COMPETING_METADATA_JSON] + $this->createRow()]
        );

        $relation = $this->createRepository()->update(
            self::KEY_ID,
            self::GROUP_ID,
            new KeyGroupRelationUpdate(sorter: 5)
        );

        $row = $this->getWrittenRow();

        $this->assertSame(5, $row[self::COLUMN_SORTER]);
        $this->assertSame(self::COMPETING_METADATA_JSON, $row[self::COLUMN_METADATA]);
        $this->assertSame(self::COMPETING_METADATA, $relation->getMetadata());
    }

    /**
     * The group before the relation, which is the order every writer of this family takes these tables
     * in; reaching them the other way round closes a cycle InnoDB resolves with a deadlock.
     */
    public function testUpdateHoldsTheGroupBeforeTheAssignmentRow(): void
    {
        $this->stubDatabase([$this->createRow()], [self::STORE_ID, self::STORED_SUB_GROUP]);

        $this->createRepository()->update(self::KEY_ID, self::GROUP_ID, new KeyGroupRelationUpdate(sorter: 5));

        $this->assertSame(
            [GroupConfigDao::TABLE_NAME_GROUPS, KeyGroupRelationDao::TABLE_NAME_RELATIONS],
            $this->getLockedTablesInOrder()
        );
    }

    /**
     * The create path reports the three columns its write preserved from the stored row, so its
     * read-back is exposed to the same interleaving and takes the same lock.
     */
    public function testCreateOrUpdateHoldsTheAssignmentRowAcrossItsReadBack(): void
    {
        $this->stubDatabase([$this->createRow()]);

        $this->createRepository()->createOrUpdate(self::KEY_ID, self::GROUP_ID, 2, true);

        $this->assertSame(
            [GroupConfigDao::TABLE_NAME_GROUPS, KeyGroupRelationDao::TABLE_NAME_RELATIONS],
            $this->getLockedTablesInOrder()
        );
    }

    /**
     * The tables the write path took a lock on, in the order it took them.
     *
     * @return list<string>
     */
    private function getLockedTablesInOrder(): array
    {
        $tables = [];
        foreach ($this->getLockingReads() as $query) {
            $this->assertStringContainsString(' FOR UPDATE', $query, 'Expected a locking read.');

            $tables[] = str_contains($query, GroupConfigDao::TABLE_NAME_GROUPS)
                ? GroupConfigDao::TABLE_NAME_GROUPS
                : KeyGroupRelationDao::TABLE_NAME_RELATIONS;
        }

        return $tables;
    }

    private function createRepository(): KeyGroupRelationRepository
    {
        return new KeyGroupRelationRepository(
            $this->makeEmpty(ListingFilterInterface::class),
            $this->getStubbedConnection()
        );
    }

    /**
     * The relation row joined with the key row, the shape the relation listing loads.
     *
     * @return array<string, mixed>
     */
    private function createRow(): array
    {
        return [
            'groupId' => self::GROUP_ID,
            'keyId' => self::KEY_ID,
            self::COLUMN_SORTER => 3,
            self::COLUMN_MANDATORY => 1,
            self::COLUMN_METADATA => self::STORED_METADATA_JSON,
            self::COLUMN_DEFINITION_OVERRIDE => self::STORED_DEFINITION_OVERRIDE_JSON,
            self::COLUMN_SUB_GROUP => self::STORED_SUB_GROUP,
            'name' => 'weight',
            'description' => '',
            'type' => 'input',
            'definition' => '{"fieldtype":"input"}',
            'enabled' => 1,
        ];
    }
}
