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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexFlagResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryMapperInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
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

    private const int STORED_SORTER = 3;

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

    private const string INDEXED_MEMBER = 'indexed';

    private const string KEY_DEFINITION_WITHOUT_DEFAULT = '{"fieldtype":"input"}';

    private const string KEY_DEFINITION_NOT_INDEXED = '{"fieldtype":"input","indexed":false}';

    private const string SEEDED_METADATA_JSON = '{"indexed":false}';

    /**
     * How often the write read the key configuration, which a create does once and an update not at
     * all.
     */
    private int $keyLookups = 0;

    public function _before(): void
    {
        $this->keyLookups = 0;
    }

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
     * A property carrying a value is written, and the response reports the value the write left on the
     * row rather than the one the row carried before it.
     *
     * @dataProvider perAssignmentValueProvider
     *
     * @param list<mixed> $subGroupResolution
     */
    public function testAPropertyCarryingAValueWritesItAndReportsIt(
        string $column,
        mixed $value,
        mixed $writtenValue,
        array $subGroupResolution,
    ): void {
        $this->stubDatabase([$this->createRow()], $subGroupResolution);

        $relation = $this->createRepository()->createOrUpdate($this->createPayload([$column => $value]));

        $this->assertSame($writtenValue, $this->getWrittenRow()[$column]);
        $this->assertSame($value, $this->readPerAssignment($relation, $column));
    }

    /**
     * The response is the row as the write left it, so a changed value is reported as changed. Read
     * from the seeded fixture instead, every one of these would report the value it had before.
     */
    public function testAChangedPropertyIsReportedFromTheStoredRow(): void
    {
        $this->stubDatabase([$this->createRow()]);

        $relation = $this->createRepository()->createOrUpdate(
            $this->createPayload([self::COLUMN_METADATA => self::COMPETING_METADATA])
        );

        $this->assertSame(self::COMPETING_METADATA_JSON, $this->getWrittenRow()[self::COLUMN_METADATA]);
        $this->assertSame(self::COMPETING_METADATA, $relation->getMetadata());
    }

    /**
     * And a cleared one is reported as cleared, rather than still naming what the row carried.
     *
     * @dataProvider perAssignmentColumnProvider
     */
    public function testAClearedPropertyIsReportedAsCleared(string $column): void
    {
        $this->stubDatabase([$this->createRow()]);

        $relation = $this->createRepository()->createOrUpdate($this->createPayload([$column => null]));

        $this->assertNull($this->readPerAssignment($relation, $column));
    }

    /**
     * A pair with no row yet is created, and the response is built from the row the write created - the
     * read-back is the only thing that has it, because the instance the write saved carries no key side
     * columns at all.
     */
    public function testANewAssignmentIsReportedFromTheRowItCreated(): void
    {
        $this->stubDatabase([], [self::STORE_ID, self::STORED_SUB_GROUP]);
        $this->letTheKeyExistForNewAssignments($this->createKeyColumns());

        $relation = $this->createRepository()->createOrUpdate(new KeyGroupRelationCreate(
            keyId: self::KEY_ID,
            groupId: self::GROUP_ID,
            sorter: 5,
            mandatory: true,
            subGroup: self::STORED_SUB_GROUP,
            metadata: self::STORED_METADATA
        ));

        $this->assertSame(5, $relation->getSorter());
        $this->assertTrue($relation->isMandatory());
        $this->assertSame(self::STORED_SUB_GROUP, $relation->getSubGroup());
        $this->assertSame(self::STORED_METADATA, $relation->getMetadata());
        $this->assertNull($relation->getDefinitionOverride());
        $this->assertSame('weight', $relation->getName());
    }

    /**
     * The defect this pins: a metadata only update used to reorder the key it was updating and drop its
     * mandatory flag, because both setters ran with the schema defaults of a payload that named neither.
     * Core has no initialization guard for these two columns - every save writes them - so the stored
     * values have to be read and re-supplied, and the response reports them too.
     */
    public function testOmittedSorterAndMandatoryKeepTheirStoredValues(): void
    {
        $this->stubDatabase([$this->createRow()]);

        $relation = $this->createRepository()->createOrUpdate($this->createPairOnlyPayload([
            self::COLUMN_METADATA => self::COMPETING_METADATA,
        ]));

        $row = $this->getWrittenRow();

        $this->assertSame(self::STORED_SORTER, $row[self::COLUMN_SORTER]);
        $this->assertSame(1, $row[self::COLUMN_MANDATORY]);
        $this->assertSame(self::STORED_SORTER, $relation->getSorter());
        $this->assertTrue($relation->isMandatory());
    }

    /**
     * The other half of the same distinction: an explicitly supplied 0 and false are values, and they
     * overwrite a stored 3 and true. The two used to default to exactly these values, so neither state
     * could be told from the other.
     */
    public function testAnExplicitlySuppliedZeroAndFalseOverwriteTheStoredValues(): void
    {
        $this->stubDatabase([$this->createRow()]);

        $relation = $this->createRepository()->createOrUpdate(
            new KeyGroupRelationCreate(self::KEY_ID, self::GROUP_ID, 0, false)
        );

        $row = $this->getWrittenRow();

        $this->assertSame(0, $row[self::COLUMN_SORTER]);
        $this->assertSame(0, $row[self::COLUMN_MANDATORY]);
        $this->assertSame(0, $relation->getSorter());
        $this->assertFalse($relation->isMandatory());
    }

    /**
     * A pair with no row yet has no stored value to keep, so omitting the two creates the assignment the
     * released endpoint created: sorted first and not mandatory.
     */
    public function testANewAssignmentOmittingSorterAndMandatoryTakesTheDefaults(): void
    {
        $this->stubDatabase([]);
        $this->letTheKeyExistForNewAssignments($this->createKeyColumns());

        $relation = $this->createRepository()->createOrUpdate($this->createPairOnlyPayload());

        $row = $this->getWrittenRow();

        $this->assertSame(0, $row[self::COLUMN_SORTER]);
        $this->assertSame(0, $row[self::COLUMN_MANDATORY]);
        $this->assertSame(0, $relation->getSorter());
        $this->assertFalse($relation->isMandatory());
    }

    /**
     * Both columns are nullable, and a row written before anything was stored in them carries NULL,
     * which reads as the value a new relation takes rather than reaching core's `int` setter as null.
     */
    public function testAStoredNullSorterAndMandatoryReadAsTheDefaults(): void
    {
        $this->stubDatabase([
            [self::COLUMN_SORTER => null, self::COLUMN_MANDATORY => null] + $this->createRow(),
        ]);

        $relation = $this->createRepository()->createOrUpdate($this->createPairOnlyPayload());

        $row = $this->getWrittenRow();

        $this->assertSame(0, $row[self::COLUMN_SORTER]);
        $this->assertSame(0, $row[self::COLUMN_MANDATORY]);
        $this->assertSame(0, $relation->getSorter());
        $this->assertFalse($relation->isMandatory());
    }

    /**
     * The flag travels inside the metadata the released endpoints already carry in both directions,
     * so an assignment can be marked not-indexed and back and is reported as stored each time.
     *
     * @dataProvider indexedFlagProvider
     */
    public function testTheFlagIsWrittenAndReportedBothWaysRound(string $storedMetadataJson, bool $indexed): void
    {
        $this->stubDatabase([[self::COLUMN_METADATA => $storedMetadataJson] + $this->createRow()]);

        $relation = $this->createRepository(null)->createOrUpdate(
            $this->createPayload([self::COLUMN_METADATA => [self::INDEXED_MEMBER => $indexed]])
        );

        $this->assertSame(
            $indexed ? '{"indexed":true}' : self::SEEDED_METADATA_JSON,
            $this->getWrittenRow()[self::COLUMN_METADATA]
        );
        $this->assertSame([self::INDEXED_MEMBER => $indexed], $relation->getMetadata());
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function indexedFlagProvider(): array
    {
        return [
            'marking a stored assignment as not indexed' => [self::STORED_METADATA_JSON, false],
            'marking it back as indexed' => [self::SEEDED_METADATA_JSON, true],
        ];
    }

    /**
     * A create whose payload would leave the assignment without the flag takes the one its key
     * states - whether the payload leaves `metadata` out, sends it as null, or sends an object
     * naming other members, which keep their place alongside the seeded one.
     *
     * @dataProvider createdWithoutTheFlagProvider
     *
     * @param array<string, mixed> $perAssignment
     * @param array<string, mixed> $expected
     */
    public function testACreatedAssignmentWithoutTheFlagTakesTheKeyDefault(
        array $perAssignment,
        string $expectedJson,
        array $expected,
    ): void {
        $this->stubDatabase([]);
        $this->letTheKeyExistForNewAssignments($this->createKeyColumns());

        $relation = $this->createRepository(self::KEY_DEFINITION_NOT_INDEXED)
            ->createOrUpdate($this->createPairOnlyPayload($perAssignment));

        $this->assertSame($expectedJson, $this->getWrittenRow()[self::COLUMN_METADATA]);
        $this->assertSame($expected, $relation->getMetadata());
        $this->assertSame(1, $this->keyLookups);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: array<string, mixed>}>
     */
    public static function createdWithoutTheFlagProvider(): array
    {
        return [
            'metadata absent from the payload' => [
                [],
                self::SEEDED_METADATA_JSON,
                [self::INDEXED_MEMBER => false],
            ],
            'metadata sent as an explicit null' => [
                [self::COLUMN_METADATA => null],
                self::SEEDED_METADATA_JSON,
                [self::INDEXED_MEMBER => false],
            ],
            'metadata naming other members only' => [
                [self::COLUMN_METADATA => self::STORED_METADATA],
                '{"source":"erp","indexed":false}',
                self::STORED_METADATA + [self::INDEXED_MEMBER => false],
            ],
        ];
    }

    /**
     * An explicit flag in the payload is the only thing that overrides the key, so the key is not
     * consulted at all - not even to be overruled by the opposite value.
     */
    public function testACreatedAssignmentStatingTheFlagKeepsItAndNeverReadsTheKey(): void
    {
        $this->stubDatabase([]);
        $this->letTheKeyExistForNewAssignments($this->createKeyColumns());

        $relation = $this->createRepository(self::KEY_DEFINITION_NOT_INDEXED)->createOrUpdate(
            $this->createPairOnlyPayload([self::COLUMN_METADATA => [self::INDEXED_MEMBER => true]])
        );

        $this->assertSame('{"indexed":true}', $this->getWrittenRow()[self::COLUMN_METADATA]);
        $this->assertSame([self::INDEXED_MEMBER => true], $relation->getMetadata());
        $this->assertSame(0, $this->keyLookups, 'The key must not be consulted here.');
    }

    /**
     * A key stating no flag seeds nothing: an absent `metadata` stays out of the write altogether
     * and a supplied object is written as it arrived. Nothing is backfilled by the back door.
     *
     * @dataProvider createdUnderAKeyWithoutADefaultProvider
     *
     * @param array<string, mixed> $perAssignment
     */
    public function testAKeyStatingNoDefaultSeedsNothing(array $perAssignment, ?string $expectedJson): void
    {
        $this->stubDatabase([]);
        $this->letTheKeyExistForNewAssignments($this->createKeyColumns());

        $this->createRepository()->createOrUpdate($this->createPairOnlyPayload($perAssignment));

        $row = $this->getWrittenRow();

        if ($expectedJson === null) {
            $this->assertArrayNotHasKey(self::COLUMN_METADATA, $row);

            return;
        }

        $this->assertSame($expectedJson, $row[self::COLUMN_METADATA]);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: ?string}>
     */
    public static function createdUnderAKeyWithoutADefaultProvider(): array
    {
        return [
            'an absent metadata is left out of the write' => [[], null],
            'a supplied object is written unchanged' => [
                [self::COLUMN_METADATA => self::STORED_METADATA],
                self::STORED_METADATA_JSON,
            ],
        ];
    }

    /**
     * An update is never seeded: there is a prior value to keep or to clear, so the key is not
     * consulted and an absent `metadata` leaves the stored flag exactly where it is.
     */
    public function testAnUpdateKeepsAStoredFlagAndNeverReadsTheKey(): void
    {
        $this->stubDatabase([[self::COLUMN_METADATA => self::SEEDED_METADATA_JSON] + $this->createRow()]);

        $relation = $this->createRepository(null)->createOrUpdate($this->createPairOnlyPayload());

        $this->assertArrayNotHasKey(self::COLUMN_METADATA, $this->getWrittenRow());
        $this->assertSame([self::INDEXED_MEMBER => false], $relation->getMetadata());
    }

    /**
     * And an explicit null still clears it, which is the rule the released endpoint settled on for
     * every per assignment column.
     */
    public function testAnUpdateWithAnExplicitNullClearsAStoredFlag(): void
    {
        $this->stubDatabase([[self::COLUMN_METADATA => self::SEEDED_METADATA_JSON] + $this->createRow()]);

        $relation = $this->createRepository(null)->createOrUpdate(
            $this->createPairOnlyPayload([self::COLUMN_METADATA => null])
        );

        $row = $this->getWrittenRow();

        $this->assertArrayHasKey(self::COLUMN_METADATA, $row);
        $this->assertNull($row[self::COLUMN_METADATA]);
        $this->assertNull($relation->getMetadata());
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

    /**
     * A payload naming the pair and nothing else beside what it is given, so that every other property
     * is absent rather than carrying a default.
     *
     * @param array<string, mixed> $perAssignment
     */
    private function createPairOnlyPayload(array $perAssignment = []): KeyGroupRelationCreate
    {
        return new KeyGroupRelationCreate(self::KEY_ID, self::GROUP_ID, ...$perAssignment);
    }

    /**
     * @param string|null $keyDefinition the `definition` of the key the payload names, null for a
     *                                   lookup this write must not make
     */
    private function createRepository(
        ?string $keyDefinition = self::KEY_DEFINITION_WITHOUT_DEFAULT,
    ): KeyGroupRelationRepository {
        return new KeyGroupRelationRepository(
            $this->makeEmpty(ListingFilterInterface::class),
            $this->getStubbedConnection(),
            $this->makeEmpty(ArtifactEntryMapperInterface::class),
            $this->makeEmpty(KeyRepositoryInterface::class, [
                'getById' => function () use ($keyDefinition): KeyConfig {
                    $this->assertNotNull($keyDefinition, 'The key configuration must not be read here.');
                    $this->keyLookups++;

                    return $this->createKeyConfig($keyDefinition);
                },
            ]),
            new SearchIndexFlagResolver()
        );
    }

    private function createKeyConfig(string $definition): KeyConfig
    {
        $config = new KeyConfig();
        $config->setId(self::KEY_ID);
        $config->setName('weight');
        $config->setType('input');
        $config->setDefinition($definition);

        return $config;
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
            self::COLUMN_SORTER => self::STORED_SORTER,
            self::COLUMN_MANDATORY => 1,
            self::COLUMN_METADATA => self::STORED_METADATA_JSON,
            self::COLUMN_DEFINITION_OVERRIDE => self::STORED_DEFINITION_OVERRIDE_JSON,
            self::COLUMN_SUB_GROUP => self::STORED_SUB_GROUP,
        ] + $this->createKeyColumns();
    }

    /**
     * The key side of the joined row, which is what a relation the write inserts joins to.
     *
     * @return array<string, mixed>
     */
    private function createKeyColumns(): array
    {
        return [
            'name' => 'weight',
            'description' => '',
            'type' => 'input',
            'definition' => '{"fieldtype":"input"}',
            'enabled' => 1,
        ];
    }

    private function readPerAssignment(KeyGroupRelation $relation, string $column): mixed
    {
        return match ($column) {
            self::COLUMN_DEFINITION_OVERRIDE => $relation->getDefinitionOverride(),
            self::COLUMN_SUB_GROUP => $relation->getSubGroup(),
            default => $relation->getMetadata(),
        };
    }
}
