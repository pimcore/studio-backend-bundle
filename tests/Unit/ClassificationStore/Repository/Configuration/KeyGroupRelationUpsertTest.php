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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationCreate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao as KeyGroupRelationDao;
use function str_contains;

/**
 * The upsert behind `POST /classification-store/configuration/key-group-relations/add` is the only
 * write surface of the three per assignment columns, so an absent property has to leave the stored
 * value alone while an explicit null clears it, and the response has to report the row as stored
 * rather than the bare instance the write handed over.
 *
 * There is no database in the unit suite, so the write is observed where it is observable at all: on
 * the row that reaches the connection, and on the statements the path issued before it (see the note
 * in SubGroupNameUniquenessTest).
 *
 * @internal
 */
final class KeyGroupRelationUpsertTest extends Unit
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

    /**
     * All three properties reach the addressed `(groupId, keyId)` row in one write - the row is
     * upserted, never deleted and recreated.
     */
    public function testAllThreePropertiesAreWrittenOntoTheAddressedRowInOneSave(): void
    {
        $this->stubDatabase([$this->createRow()], [self::STORE_ID, self::STORED_SUB_GROUP]);

        $this->createRepository()->createOrUpdate(new KeyGroupRelationCreate(
            keyId: self::KEY_ID,
            groupId: self::GROUP_ID,
            sorter: 5,
            mandatory: true,
            definitionOverride: self::STORED_DEFINITION_OVERRIDE,
            subGroup: self::STORED_SUB_GROUP,
            metadata: self::STORED_METADATA
        ));

        $row = $this->getWrittenRow();

        $this->assertSame(self::GROUP_ID, $row['groupId']);
        $this->assertSame(self::KEY_ID, $row['keyId']);
        $this->assertSame(5, $row[self::COLUMN_SORTER]);
        $this->assertSame(1, $row[self::COLUMN_MANDATORY]);
        $this->assertSame(self::STORED_DEFINITION_OVERRIDE_JSON, $row[self::COLUMN_DEFINITION_OVERRIDE]);
        $this->assertSame(self::STORED_SUB_GROUP, $row[self::COLUMN_SUB_GROUP]);
        $this->assertSame(self::STORED_METADATA_JSON, $row[self::COLUMN_METADATA]);
    }

    /**
     * A property absent from the payload never reaches core's setter, so core's per assignment
     * initialization guard leaves the column out of the write altogether.
     *
     * @dataProvider perAssignmentColumnProvider
     */
    public function testAnAbsentPropertyIsNotWritten(string $column): void
    {
        $this->stubDatabase([$this->createRow()]);

        $this->createRepository()->createOrUpdate($this->createPayload());

        $this->assertArrayNotHasKey($column, $this->getWrittenRow());
    }

    /**
     * An explicit null is a value: the setter is called with it and the column is written NULL.
     *
     * @dataProvider perAssignmentColumnProvider
     */
    public function testAPropertyThatIsExplicitlyNullClearsTheColumn(string $column): void
    {
        $this->stubDatabase([$this->createRow()]);

        $this->createRepository()->createOrUpdate($this->createPayload([$column => null]));

        $row = $this->getWrittenRow();

        $this->assertArrayHasKey($column, $row);
        $this->assertNull($row[$column]);
    }

    /**
     * @dataProvider perAssignmentValueProvider
     *
     * @param list<mixed> $subGroupResolution
     */
    public function testAPropertyCarryingAValueWritesIt(
        string $column,
        mixed $value,
        mixed $writtenValue,
        array $subGroupResolution,
    ): void {
        $this->stubDatabase([$this->createRow()], $subGroupResolution);

        $this->createRepository()->createOrUpdate($this->createPayload([$column => $value]));

        $this->assertSame($writtenValue, $this->getWrittenRow()[$column]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function perAssignmentColumnProvider(): array
    {
        return [
            'definition override' => [self::COLUMN_DEFINITION_OVERRIDE],
            'sub group' => [self::COLUMN_SUB_GROUP],
            'metadata' => [self::COLUMN_METADATA],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: mixed, 2: mixed, 3: list<mixed>}>
     */
    public static function perAssignmentValueProvider(): array
    {
        return [
            'definition override' => [
                self::COLUMN_DEFINITION_OVERRIDE,
                self::STORED_DEFINITION_OVERRIDE,
                self::STORED_DEFINITION_OVERRIDE_JSON,
                [],
            ],
            'sub group' => [
                self::COLUMN_SUB_GROUP,
                self::STORED_SUB_GROUP,
                self::STORED_SUB_GROUP,
                [self::STORE_ID, self::STORED_SUB_GROUP],
            ],
            'metadata' => [
                self::COLUMN_METADATA,
                self::STORED_METADATA,
                self::STORED_METADATA_JSON,
                [],
            ],
        ];
    }

    /**
     * The released payload - only the pair, the sorter and the mandatory flag - leaves an already
     * stored definition override, sub group and metadata intact, and the response reports them:
     * the instance the write saved carries none of the three, so they can only come from the stored
     * row read back afterwards.
     */
    public function testAPayloadNamingNoneOfThemKeepsAndReportsTheStoredValues(): void
    {
        $this->stubDatabase([$this->createRow()]);

        $relation = $this->createRepository()->createOrUpdate($this->createPayload());

        $row = $this->getWrittenRow();

        $this->assertArrayNotHasKey(self::COLUMN_DEFINITION_OVERRIDE, $row);
        $this->assertArrayNotHasKey(self::COLUMN_SUB_GROUP, $row);
        $this->assertArrayNotHasKey(self::COLUMN_METADATA, $row);
        $this->assertSame(self::STORED_DEFINITION_OVERRIDE, $relation->getDefinitionOverride());
        $this->assertSame(self::STORED_SUB_GROUP, $relation->getSubGroup());
        $this->assertSame(self::STORED_METADATA, $relation->getMetadata());
    }

    /**
     * A definition override naming anything but the three overridable properties is refused by core,
     * which is the fault of the request: 422 with core's own message, not the 500 a failed save is.
     */
    public function testARefusedDefinitionOverrideIsAnUnprocessableContentThatWritesNothing(): void
    {
        $this->stubDatabase([$this->createRow()]);

        try {
            $this->createRepository()->createOrUpdate(
                $this->createPayload([self::COLUMN_DEFINITION_OVERRIDE => ['fieldtype' => 'select']])
            );
            $this->fail('Expected the refused definition override to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertSame('"fieldtype" cannot be overridden per key assignment.', $e->getMessage());
        }

        $this->assertNothingWasWritten();
        $this->assertSame(['begin', 'rollback'], $this->getTransactionSteps());
    }

    /**
     * A sub group naming no entry of the store's vocabulary is refused by core's own resolution, and
     * arrives as the same 422 carrying its message.
     */
    public function testAnUnknownSubGroupIsAnUnprocessableContentThatWritesNothing(): void
    {
        $this->stubDatabase([$this->createRow()], [self::STORE_ID, false]);

        try {
            $this->createRepository()->createOrUpdate($this->createPayload([self::COLUMN_SUB_GROUP => 'nope']));
            $this->fail('Expected the unknown sub group to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertSame(
                'The sub group "nope" does not exist in the store of group 7.',
                $e->getMessage()
            );
        }

        $this->assertNothingWasWritten();
        $this->assertSame(['begin', 'rollback'], $this->getTransactionSteps());
    }

    /**
     * What is stored and published is the identifier core resolved to, not the spelling that was
     * passed - the column collation is case insensitive, so two spellings name one entry.
     */
    public function testTheSubGroupIdentifierCoreResolvedToIsWrittenAndReported(): void
    {
        $this->stubDatabase([$this->createRow()], [self::STORE_ID, self::STORED_SUB_GROUP]);

        $relation = $this->createRepository()->createOrUpdate(
            $this->createPayload([self::COLUMN_SUB_GROUP => 'dimensions'])
        );

        $this->assertSame(self::STORED_SUB_GROUP, $this->getWrittenRow()[self::COLUMN_SUB_GROUP]);
        $this->assertSame(self::STORED_SUB_GROUP, $relation->getSubGroup());
    }

    /**
     * The group before the relation, which is the order every writer of this family takes these tables
     * in; reaching them the other way round closes a cycle InnoDB resolves with a deadlock.
     */
    public function testTheGroupIsHeldBeforeTheAssignmentRowInsideOneTransaction(): void
    {
        $this->stubDatabase([$this->createRow()]);

        $this->createRepository()->createOrUpdate($this->createPayload());

        $this->assertSame(
            [GroupConfigDao::TABLE_NAME_GROUPS, KeyGroupRelationDao::TABLE_NAME_RELATIONS],
            $this->getLockedTablesInOrder()
        );
        $this->assertSame(['begin', 'commit'], $this->getTransactionSteps());
    }

    /**
     * The defect this pins: with the read-back outside the lock, a competing writer committing between
     * the write and the read had the response name a value this request never wrote, and a read taken
     * before the lock could not see it at all.
     *
     * The competing writer here commits its metadata update while this request waits for the row. The
     * payload carries only a sorter, so the metadata the write leaves alone stays the competitor's and
     * the response reports it - which is only true if the row was read after the lock was granted.
     */
    public function testAnOmittedPropertyACompetingWriterCommittedFirstIsKeptAndReported(): void
    {
        $this->stubDatabase([$this->createRow()]);
        $this->letACompetingWriterCommitOnTheRelationLock(
            fn (): array => [[self::COLUMN_METADATA => self::COMPETING_METADATA_JSON] + $this->createRow()]
        );

        $relation = $this->createRepository()->createOrUpdate($this->createPayload());

        $this->assertArrayNotHasKey(self::COLUMN_METADATA, $this->getWrittenRow());
        $this->assertSame(self::COMPETING_METADATA, $relation->getMetadata());
    }

    /**
     * A consequence of reading the row back: a pair whose key id names no key configuration has no row
     * for the read to find, so the request is refused and the transaction takes the write with it
     * instead of leaving an assignment that points at nothing.
     */
    public function testAPairThatCannotBeReadBackIsRefusedAndRolledBack(): void
    {
        $this->stubDatabase([]);

        try {
            $this->createRepository()->createOrUpdate($this->createPayload());
            $this->fail('Expected the pair that cannot be read back to be refused.');
        } catch (NotFoundException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }

        $this->assertSame(['begin', 'rollback'], $this->getTransactionSteps());
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

    /**
     * The released payload, optionally carrying one of the three new properties.
     *
     * @param array<string, mixed> $perAssignment
     */
    private function createPayload(array $perAssignment = []): KeyGroupRelationCreate
    {
        return new KeyGroupRelationCreate(self::KEY_ID, self::GROUP_ID, 2, true, ...$perAssignment);
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
