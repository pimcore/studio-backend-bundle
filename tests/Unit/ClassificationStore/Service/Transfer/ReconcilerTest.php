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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\Transfer;

use const JSON_THROW_ON_ERROR;
use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\StoreRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTriggerInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyDiagnostics;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyRefusals;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactAssignmentEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactDocument;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryMapper;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryWriter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactKeyEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactRelationWriter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactTargetMatcher;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\Reconciler;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedCollectionRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function array_filter;
use function array_values;
use function implode;
use function json_encode;
use function sprintf;

/**
 * Apply creates, updates and adopts, and never deletes. The repositories are mocked, so what each
 * test asserts on is the set of writes the reconciler asked for - a write that was not asked for is
 * one no model ever saw.
 *
 * @internal
 */
final class ReconcilerTest extends Unit
{
    private const int STORE_ID = 1;

    private const int GROUP_ID = 41;

    private const int KEY_ID = 51;

    private const int COLLECTION_ID = 31;

    private const string STORE_IDENTIFIER = '01STORE';

    private const string GROUP_IDENTIFIER = '01GROUP';

    private const string KEY_IDENTIFIER = '01KEY';

    private const string SUB_GROUP_IDENTIFIER = '01SUBGROUP';

    private const string COLLECTION_IDENTIFIER = '01COLLECTION';

    private const string GROUP_NAME = 'Measurements';

    private const string KEY_NAME = 'weight';

    private const string GROUP_WRITE = 'group:%s:%s';

    private const string INDEXED_MEMBER = 'indexed';

    private const string KEY_DEFINITION = '{"fieldtype":"input","title":"Weight"}';

    /**
     * @var list<string>
     */
    private array $writes = [];

    /**
     * @var list<KeyGroupRelation>
     */
    private array $savedAssignments = [];

    /**
     * The writes and the locks in the order the apply reached them, so that a test can assert which
     * came first. `$writes` stays what was written; the locks are not writes.
     *
     * @var list<string>
     */
    private array $steps = [];

    /**
     * Whether each probe of the groups a key is assigned to was asked for under a lock.
     *
     * @var list<bool>
     */
    private array $disableProbesHeld = [];

    /**
     * The identifier is what matches, so a renamed row is updated rather than duplicated.
     */
    public function testAnIdentifierHitUpdatesTheRowItNames(): void
    {
        $reconciler = $this->createReconciler(
            groups: [$this->createGroup(self::GROUP_IDENTIFIER, 'Old name')]
        );

        $report = $reconciler->apply($this->createDocument(), $this->createStore(), false);

        $this->assertSame(1, $report->getCount(ApplyOutcome::UPDATED));
        $this->assertSame(0, $report->getCount(ApplyOutcome::ADOPTED));
        $this->assertSame(
            [sprintf('group "%s"', self::GROUP_NAME)],
            $report->getNamed(ApplyOutcome::UPDATED)
        );
        $this->assertContains(
            sprintf(self::GROUP_WRITE, self::GROUP_IDENTIFIER, self::GROUP_NAME),
            $this->writes
        );
    }

    /**
     * A row that carries no identifier is the only row a name may match, and the file's identifier
     * is stamped onto it. That is the third category beside created and updated.
     */
    public function testANullIdentifierNameHitAdoptsTheRow(): void
    {
        $reconciler = $this->createReconciler(
            groups: [$this->createGroup(null, self::GROUP_NAME)]
        );

        $report = $reconciler->apply($this->createDocument(), $this->createStore(), false);

        $this->assertSame(1, $report->getCount(ApplyOutcome::ADOPTED));
        $this->assertSame(0, $report->getCount(ApplyOutcome::UPDATED));
        $this->assertSame([sprintf('group "%s"', self::GROUP_NAME)], $report->getNamed(ApplyOutcome::ADOPTED));
        $this->assertContains(
            sprintf(self::GROUP_WRITE, self::GROUP_IDENTIFIER, self::GROUP_NAME),
            $this->writes
        );
    }

    public function testAnEntryTheTargetDoesNotCarryIsCreated(): void
    {
        $reconciler = $this->createReconciler();

        $report = $reconciler->apply($this->createDocument(), $this->createStore(), false);

        // the group, the key and the assignment between them; the store already matched
        $this->assertSame(3, $report->getCount(ApplyOutcome::CREATED));
        $this->assertContains(
            sprintf(self::GROUP_WRITE, self::GROUP_IDENTIFIER, self::GROUP_NAME),
            $this->writes
        );
        $this->assertContains(
            sprintf('key:%s:%s', self::KEY_IDENTIFIER, self::KEY_NAME),
            $this->writes
        );
    }

    /**
     * Create or update, never delete: a group and an assignment the store carries and the file does
     * not are named in the report and nothing is written for them.
     */
    public function testWhatTheStoreCarriesAndTheFileDoesNotIsNamedAndLeftInPlace(): void
    {
        $extraGroup = $this->createGroup('01OTHERGROUP', 'Logistics', id: 42);
        $extraAssignment = $this->createAssignment('01OTHERGROUP', '01OTHERKEY', groupId: 42, keyId: 52);

        $reconciler = $this->createReconciler(
            groups: [$this->createGroup(self::GROUP_IDENTIFIER, self::GROUP_NAME), $extraGroup],
            keyAssignments: [$extraAssignment]
        );

        $report = $reconciler->apply($this->createDocument(), $this->createStore(), false);

        $this->assertSame(2, $report->getCount(ApplyOutcome::TARGET_ONLY));
        $this->assertSame(
            ['group "Logistics"', 'key assignment "01OTHERGROUP -> 01OTHERKEY"'],
            $report->getNamed(ApplyOutcome::TARGET_ONLY)
        );
        $this->assertNotContains('group:01OTHERGROUP:Logistics', $this->writes);
    }

    /**
     * The name of a row that already carries another identifier is not adoptable: applying the file
     * would have to write two rows under one name.
     */
    public function testANameTakenByARowWithAnotherIdentifierIsRefused(): void
    {
        $reconciler = $this->createReconciler(
            groups: [$this->createGroup('01DIFFERENT', self::GROUP_NAME)]
        );

        $report = $reconciler->apply($this->createDocument(), $this->createStore(), false);

        $this->assertTrue($report->hasConflicts());
        $this->assertStringContainsString(
            'already carries another identifier',
            implode(' ', $report->getNamed(ApplyOutcome::CONFLICT))
        );
    }

    /**
     * The same two rows the other way round. Whether a namesake is a refusal or a target-only row
     * turns on whether some other row of the level answers for the entry it shares a name with -
     * which is a fact about the level, not about the row the chunked walk reaches first. Two groups
     * called `Measurements` carrying `01GROUP` and `01DIFFERENT`, a file naming only `01GROUP`:
     * `01GROUP` is matched and the namesake is left in place, whichever of the two has the lower row
     * id. Read mid-walk, the row order alone decided it, and an unchanged import failed on the one
     * ordering.
     *
     * @dataProvider namesakeRowOrders
     */
    public function testANamesakeIsLeftInPlaceWhateverOrderTheRowsComeBackIn(bool $ownerFirst): void
    {
        $owner = $this->createGroup(self::GROUP_IDENTIFIER, self::GROUP_NAME, id: self::GROUP_ID);
        $namesake = $this->createGroup('01DIFFERENT', self::GROUP_NAME, id: self::GROUP_ID + 1);

        $report = $this->createReconciler(groups: $ownerFirst ? [$owner, $namesake] : [$namesake, $owner])
            ->apply($this->createDocument(), $this->createStore(), false);

        $this->assertFalse(
            $report->hasConflicts(),
            implode(' ', $report->getNamed(ApplyOutcome::CONFLICT))
        );
        $this->assertSame(
            [sprintf('group "%s"', self::GROUP_NAME)],
            $report->getNamed(ApplyOutcome::TARGET_ONLY)
        );
    }

    /**
     * The same, at the collection level: the level is walked by the one shared path, so a collection
     * namesake is answered for the same way a group's is.
     *
     * @dataProvider namesakeRowOrders
     */
    public function testACollectionNamesakeIsLeftInPlaceWhateverOrderTheRowsComeBackIn(bool $ownerFirst): void
    {
        $owner = $this->createCollection(self::COLLECTION_IDENTIFIER, 'Lighting', id: self::COLLECTION_ID);
        $namesake = $this->createCollection('01DIFFERENT', 'Lighting', id: self::COLLECTION_ID + 1);

        $document = $this->createDocument([
            'collections' => [
                ['identifier' => self::COLLECTION_IDENTIFIER, 'name' => 'Lighting', 'description' => null],
            ],
        ]);

        $report = $this
            ->createReconciler(collections: $ownerFirst ? [$owner, $namesake] : [$namesake, $owner])
            ->apply($document, $this->createStore(), false);

        $this->assertFalse(
            $report->hasConflicts(),
            implode(' ', $report->getNamed(ApplyOutcome::CONFLICT))
        );
        $this->assertContains('collection "Lighting"', $report->getNamed(ApplyOutcome::TARGET_ONLY));
    }

    /**
     * The owner of the file's identifier on the lower row id, and on the higher one.
     *
     * @return iterable<string, array{bool}>
     */
    public static function namesakeRowOrders(): iterable
    {
        yield 'the owner first' => [true];
        yield 'the namesake first' => [false];
    }

    /**
     * Writing `enabled = 0` deletes every assignment of the key, so a file that disables a key and
     * assigns it in the same breath is refused rather than left to drop the row it just wrote.
     */
    public function testAKeyTheFileDisablesAndAlsoAssignsIsRefused(): void
    {
        $document = $this->createDocument(['keys' => [$this->createKeyEntry(enabled: false)]]);
        $reconciler = $this->createReconciler();

        $report = $reconciler->apply($document, $this->createStore(), false);

        $this->assertTrue($report->hasConflicts());
        $this->assertStringContainsString(
            'disabled by the file and assigned by it as well',
            implode(' ', $report->getNamed(ApplyOutcome::CONFLICT))
        );
    }

    /**
     * The same hazard from the other side: the file disables a key the store still assigns, and the
     * refusal names the groups that would lose it.
     */
    public function testAKeyTheFileDisablesThatTheStoreStillAssignsIsRefused(): void
    {
        $document = $this->createDocument([
            'keys' => [$this->createKeyEntry(enabled: false)],
            'keyGroupRelations' => [],
        ]);

        $reconciler = $this->createReconciler(
            enabledKeys: [$this->createKey(self::KEY_IDENTIFIER, self::KEY_NAME, enabled: true)],
            groupNamesByKeyId: [self::GROUP_NAME]
        );

        $report = $reconciler->apply($document, $this->createStore(), false);

        $this->assertTrue($report->hasConflicts());
        $this->assertStringContainsString(
            sprintf('still assigned to the group(s) "%s"', self::GROUP_NAME),
            implode(' ', $report->getNamed(ApplyOutcome::CONFLICT))
        );
        $this->assertNotContains(
            sprintf('key:%s:%s', self::KEY_IDENTIFIER, self::KEY_NAME),
            $this->writes
        );
    }

    /**
     * An assignment may narrow the options and the units of a key, and nothing else - above all not
     * what the key stores.
     */
    public function testADefinitionOverrideOutsideTheClosedListIsRefused(): void
    {
        $document = $this->createDocument([
            'keyGroupRelations' => [
                $this->createAssignmentEntry(['definitionOverride' => ['fieldtype' => 'select']]),
            ],
        ]);

        $report = $this->createReconciler()->apply($document, $this->createStore(), false);

        $this->assertTrue($report->hasConflicts());
        $this->assertStringContainsString(
            '"fieldtype" cannot be overridden per key assignment',
            implode(' ', $report->getNamed(ApplyOutcome::CONFLICT))
        );
    }

    /**
     * The sub group vocabulary is the store's own, and an assignment may only name an entry of it.
     */
    public function testASubGroupNeitherTheFileNorTheStoreCarriesIsRefused(): void
    {
        $document = $this->createDocument([
            'keyGroupRelations' => [$this->createAssignmentEntry(['subGroup' => '01MISSING'])],
        ]);

        $report = $this->createReconciler()->apply($document, $this->createStore(), false);

        $this->assertTrue($report->hasConflicts());
        $this->assertStringContainsString(
            'the sub group "01MISSING" exists neither in the file nor in the store',
            implode(' ', $report->getNamed(ApplyOutcome::CONFLICT))
        );
    }

    /**
     * Every conflict is named, not the first one, and the transaction is rolled back - a file that
     * is refused leaves the store exactly as it was.
     */
    public function testTwoConflictsAreBothNamedAndNothingIsWritten(): void
    {
        $document = $this->createDocument([
            'keys' => [$this->createKeyEntry(enabled: false)],
            'keyGroupRelations' => [
                $this->createAssignmentEntry(['definitionOverride' => ['fieldtype' => 'select']]),
            ],
        ]);

        $connection = $this->createMock(Connection::class);
        $connection->method('isTransactionActive')->willReturn(false);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollBack');
        $connection->expects($this->never())->method('commit');

        $reconciler = $this->createReconciler(
            groups: [$this->createGroup(self::GROUP_IDENTIFIER, self::GROUP_NAME)],
            disabledKeys: [$this->createKey(self::KEY_IDENTIFIER, self::KEY_NAME, enabled: false)],
            connection: $connection
        );

        $report = $reconciler->apply($document, $this->createStore(), false);

        $this->assertSame(2, $report->getCount(ApplyOutcome::CONFLICT));
        $this->assertSame([], $this->writes, 'A refused file must not ask for a single write.');
    }

    /**
     * The console command applies a configuration to an installation that does not carry the store
     * yet, which is what makes one file enough to set one up.
     */
    public function testACommandRunCreatesTheStoreTheFileNames(): void
    {
        $reconciler = $this->createReconciler();

        $report = $reconciler->apply($this->createDocument(), null, false);

        $this->assertContains(
            sprintf('store:%s:ETIM:The ETIM classification', self::STORE_IDENTIFIER),
            $this->writes
        );
        // the store itself, and then the group, the key and the assignment between them
        $this->assertSame(4, $report->getCount(ApplyOutcome::CREATED));
    }

    /**
     * A store rename survives a round trip: the file's name and description are written onto the
     * store it is applied to.
     */
    public function testTheStoreNameAndDescriptionAreWrittenFromTheFile(): void
    {
        $store = $this->createStore();
        $store->setName('Old name');

        $this->createReconciler()->apply($this->createDocument(), $store, false);

        $this->assertContains(
            sprintf('store:%s:ETIM:The ETIM classification', self::STORE_IDENTIFIER),
            $this->writes
        );
    }

    /**
     * The second run of the same file writes nothing. The comparison is required rather than an
     * optimisation: a key save stamps the modification date on every call and re-issues the
     * relation delete of a disabled key, so a blind re-write would churn rows a deploy is supposed
     * to leave alone.
     */
    public function testApplyingTheSameFileToItsOwnContentsWritesNothing(): void
    {
        $reconciler = $this->createReconciler(
            groups: [$this->createGroup(self::GROUP_IDENTIFIER, self::GROUP_NAME)],
            enabledKeys: [$this->createKey(self::KEY_IDENTIFIER, self::KEY_NAME, enabled: true)],
            keyAssignments: [$this->createAssignment(self::GROUP_IDENTIFIER, self::KEY_IDENTIFIER)]
        );

        $report = $reconciler->apply($this->createDocument(), $this->createStore(), false);

        $this->assertSame(0, $report->getCount(ApplyOutcome::CREATED));
        $this->assertSame(0, $report->getCount(ApplyOutcome::UPDATED));
        $this->assertSame(0, $report->getCount(ApplyOutcome::ADOPTED));
        $this->assertSame(4, $report->getCount(ApplyOutcome::MATCHED_UNCHANGED));
        $this->assertSame([], $this->writes);
    }

    /**
     * A dry run resolves no id, because it writes nothing. An entry the file itself declares is
     * still a valid reference, so the assignment that names a sub group and endpoints the file
     * creates is the creation it would be - not a refusal over entries that are right there.
     */
    public function testADryRunAcceptsAReferenceToAnEntryTheFileDeclares(): void
    {
        $document = $this->createDocument([
            'subGroups' => [
                ['identifier' => self::SUB_GROUP_IDENTIFIER, 'name' => 'Dimensions', 'description' => null],
            ],
            'keyGroupRelations' => [
                $this->createAssignmentEntry(['subGroup' => self::SUB_GROUP_IDENTIFIER]),
            ],
        ]);

        $report = $this->createReconciler()->apply($document, $this->createStore(), true);

        $this->assertFalse($report->hasConflicts(), implode(' ', $report->getNamed(ApplyOutcome::CONFLICT)));
        // the sub group, the group, the key and the assignment between them
        $this->assertSame(4, $report->getCount(ApplyOutcome::CREATED));
        $this->assertSame([], $this->writes);
    }

    /**
     * The other half of the same question: an endpoint that is in neither the file nor the store is
     * refused in a dry run as well, rather than reported as a creation no later run could make.
     */
    public function testADryRunRefusesAReferenceNothingDeclares(): void
    {
        $document = $this->createDocument([
            'keyGroupRelations' => [$this->createAssignmentEntry(['group' => '01ABSENT'])],
        ]);

        $report = $this->createReconciler()->apply($document, $this->createStore(), true);

        $this->assertTrue($report->hasConflicts());
        $this->assertStringContainsString(
            'it names "01ABSENT", which exists neither in the file nor in the store',
            implode(' ', $report->getNamed(ApplyOutcome::CONFLICT))
        );
    }

    /**
     * The store's name is another store's to carry: renaming onto it is refused where
     * `StoreRepository::update()` refuses it, and reported as a conflict of the reconcile rather
     * than left to arrive as a failed save.
     */
    public function testAStoreNameAnotherStoreCarriesIsRefused(): void
    {
        $store = $this->createStore();
        $store->setName('Old name');

        $reconciler = $this->createReconciler(storeNameIsTaken: true);

        $report = $reconciler->apply($this->createDocument(), $store, false);

        $this->assertTrue($report->hasConflicts());
        $this->assertStringContainsString(
            'the name is already another store\'s',
            implode(' ', $report->getNamed(ApplyOutcome::CONFLICT))
        );
        $this->assertNotContains(
            sprintf('store:%s:ETIM:The ETIM classification', self::STORE_IDENTIFIER),
            $this->writes
        );
    }

    /**
     * And the same for a sub group, whose name is unique per store: the file does not get to write
     * a second row under a name the store already carries just because it arrived as an artifact.
     */
    public function testASubGroupNameTheStoreAlreadyCarriesIsRefused(): void
    {
        $document = $this->createDocument([
            'subGroups' => [
                ['identifier' => self::SUB_GROUP_IDENTIFIER, 'name' => 'Dimensions', 'description' => null],
            ],
        ]);

        $reconciler = $this->createReconciler(subGroupNameIsTaken: true);

        $report = $reconciler->apply($document, $this->createStore(), false);

        $this->assertTrue($report->hasConflicts());
        $this->assertStringContainsString(
            'sub group "Dimensions": the name is already another entry\'s in this store',
            implode(' ', $report->getNamed(ApplyOutcome::CONFLICT))
        );
        $this->assertNotContains(
            sprintf('subGroup:%s:Dimensions', self::SUB_GROUP_IDENTIFIER),
            $this->writes,
            'The entry whose name is taken must not be written.'
        );
    }

    /**
     * Two identifiers of one file under one name are the same collision seen from inside the file.
     * A real run finds the second one in the row the first one left; a dry run writes nothing, so
     * it is the run's own answer that has to refuse it - in both modes, before the apply.
     */
    public function testAFileNamingTwoSubGroupsTheSameWayIsRefused(): void
    {
        $document = $this->createDocument([
            'subGroups' => [
                ['identifier' => self::SUB_GROUP_IDENTIFIER, 'name' => 'Dimensions', 'description' => null],
                ['identifier' => '01OTHERSUBGROUP', 'name' => 'dimensions', 'description' => null],
            ],
        ]);

        $report = $this->createReconciler()->apply($document, $this->createStore(), true);

        $this->assertTrue($report->hasConflicts());
        $this->assertStringContainsString(
            'sub group "dimensions": the name is already another entry\'s in this store',
            implode(' ', $report->getNamed(ApplyOutcome::CONFLICT))
        );
    }

    /**
     * A dry run reports what it would do and asks for nothing.
     */
    public function testADryRunReportsWithoutWriting(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('beginTransaction');

        $reconciler = $this->createReconciler(connection: $connection);

        $report = $reconciler->apply($this->createDocument(), $this->createStore(), true);

        $this->assertSame(3, $report->getCount(ApplyOutcome::CREATED));
        $this->assertSame([], $this->writes);
    }

    /**
     * An assignment that leaves a per assignment field out does not write it: the model's own
     * guards gate the write on the field having been set, and the mapper sets only what the entry
     * supplies.
     */
    public function testAnOmittedPerAssignmentFieldIsNotSetOnTheRelationThatIsSaved(): void
    {
        $document = $this->createDocument([
            'keyGroupRelations' => [
                [
                    'group' => self::GROUP_IDENTIFIER,
                    'key' => self::KEY_IDENTIFIER,
                    'sorter' => 1,
                    'mandatory' => false,
                ],
            ],
        ]);

        $this->createReconciler()->apply($document, $this->createStore(), false);

        $this->assertCount(1, $this->savedAssignments);
        $this->assertFalse($this->savedAssignments[0]->isMetadataInitialized());
        $this->assertFalse($this->savedAssignments[0]->isDefinitionOverrideInitialized());
        $this->assertFalse($this->savedAssignments[0]->isSubGroupInitialized());
    }

    /**
     * An assignment the file creates seeds nothing from its key, not even under a key whose own
     * definition excludes it from the search index: `metadata` is a free-form column the entry owns
     * whole, so the explicit `metadata: null` the export writes for a NULL column stays NULL.
     *
     * @dataProvider unseededAssignmentEntryProvider
     *
     * @param array<string, mixed> $overrides
     * @param array<string, mixed>|null $expected
     */
    public function testACreatedAssignmentSeedsNothingFromItsKey(array $overrides, ?array $expected): void
    {
        $document = $this->createDocument([
            'keys' => [$this->createKeyEntryCarryingTheFlag()],
            'keyGroupRelations' => [$this->createAssignmentEntry($overrides)],
        ]);

        $this
            ->createReconciler()
            ->apply($document, $this->createStore(), false);

        $this->assertCount(1, $this->savedAssignments);
        $this->assertSame($expected, $this->savedAssignments[0]->getMetadata());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>|null}>
     */
    public static function unseededAssignmentEntryProvider(): array
    {
        return [
            'an entry the export wrote with a null metadata' => [
                ['metadata' => null],
                null,
            ],
            'an entry naming other members only' => [
                ['metadata' => ['source' => 'etim']],
                ['source' => 'etim'],
            ],
        ];
    }

    /**
     * An entry that names `indexed` stores it verbatim as the ordinary free-form member it is, and
     * it is neither refused nor stripped - which is what keeps a file exported before the member
     * stopped being reserved applying afterwards.
     *
     * @dataProvider storedVerbatimMetadataProvider
     *
     * @param array<string, mixed> $metadata
     */
    public function testAnEntryNamingTheMemberStoresItVerbatim(array $metadata): void
    {
        $document = $this->createDocument([
            'keys' => [$this->createKeyEntryCarryingTheFlag()],
            'keyGroupRelations' => [$this->createAssignmentEntry(['metadata' => $metadata])],
        ]);

        $this
            ->createReconciler()
            ->apply($document, $this->createStore(), false);

        $this->assertCount(1, $this->savedAssignments);
        $this->assertSame($metadata, $this->savedAssignments[0]->getMetadata());
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function storedVerbatimMetadataProvider(): array
    {
        return [
            'the member alone' => [[self::INDEXED_MEMBER => true]],
            'the member beside another' => [['source' => 'etim', self::INDEXED_MEMBER => false]],
        ];
    }

    /**
     * An assignment the file updates writes nothing into the column either when the entry leaves
     * `metadata` out.
     */
    public function testAnUpdatedAssignmentIsNotSeeded(): void
    {
        $document = $this->createDocument([
            'keyGroupRelations' => [
                [
                    'group' => self::GROUP_IDENTIFIER,
                    'key' => self::KEY_IDENTIFIER,
                    'sorter' => 2,
                    'mandatory' => false,
                ],
            ],
        ]);

        $this
            ->createReconciler(
                groups: [$this->createGroup(self::GROUP_IDENTIFIER, self::GROUP_NAME)],
                enabledKeys: [$this->createKey(self::KEY_IDENTIFIER, self::KEY_NAME, enabled: true)],
                keyAssignments: [$this->createAssignment(self::GROUP_IDENTIFIER, self::KEY_IDENTIFIER)]
            )
            ->apply($document, $this->createStore(), false);

        $this->assertCount(1, $this->savedAssignments);
        $this->assertFalse($this->savedAssignments[0]->isMetadataInitialized());
    }

    /**
     * A dry run writes nothing, so it seeds nothing either, and the outcome it reports is the one it
     * reported before the flag existed.
     */
    public function testADryRunSeedsNothingAndReportsTheSameOutcome(): void
    {
        $document = $this->createDocument([
            'keys' => [$this->createKeyEntryCarryingTheFlag()],
            'keyGroupRelations' => [$this->createAssignmentEntry(['metadata' => null])],
        ]);

        $report = $this
            ->createReconciler()
            ->apply($document, $this->createStore(), true);

        $this->assertSame([], $this->writes);
        $this->assertSame([], $this->savedAssignments);
        $this->assertSame(3, $report->getCount(ApplyOutcome::CREATED));
    }

    public function testAPerAssignmentFieldSuppliedAsNullIsSetOnTheRelationThatIsSaved(): void
    {
        $document = $this->createDocument([
            'keyGroupRelations' => [$this->createAssignmentEntry(['metadata' => null])],
        ]);

        $this->createReconciler()->apply($document, $this->createStore(), false);

        $this->assertCount(1, $this->savedAssignments);
        $this->assertTrue($this->savedAssignments[0]->isMetadataInitialized());
        $this->assertNull($this->savedAssignments[0]->getMetadata());
    }

    /**
     * A name is not an identity. An identifierless key called `weight` and the key that owns the
     * file's `weight` entry can both exist, so identifier ownership is resolved across the level
     * before a name may adopt anything: the owner is updated and the namesake stays target only.
     * Adopting the namesake instead would write the file's identifier onto the wrong row and skip
     * the row that already carries it.
     */
    public function testAnIdentifierlessNamesakeDoesNotTakeTheEntryOfTheRowThatOwnsItsIdentifier(): void
    {
        $owner = $this->createKey(self::KEY_IDENTIFIER, self::KEY_NAME, enabled: true);
        $owner->setTitle('Old title');
        $namesake = $this->createKey(null, self::KEY_NAME, enabled: true, id: 52);

        $reconciler = $this->createReconciler(
            groups: [$this->createGroup(self::GROUP_IDENTIFIER, self::GROUP_NAME)],
            enabledKeys: [$owner, $namesake]
        );

        $report = $reconciler->apply($this->createDocument(), $this->createStore(), false);

        $this->assertSame(0, $report->getCount(ApplyOutcome::ADOPTED), 'The namesake must not be adopted.');
        $this->assertSame(1, $report->getCount(ApplyOutcome::UPDATED));
        $this->assertSame([sprintf('key "%s"', self::KEY_NAME)], $report->getNamed(ApplyOutcome::UPDATED));
        $this->assertSame([sprintf('key "%s"', self::KEY_NAME)], $report->getNamed(ApplyOutcome::TARGET_ONLY));
    }

    /**
     * Key names are deliberately non-unique, so the second key of a store called `weight` is the
     * target-only row it is - not a conflict that rolls the whole import back. The refusal of a
     * name the file reuses under another identifier holds for the levels whose name is unique per
     * store, and keys are not one of them.
     */
    public function testASecondKeyUnderTheSameNameIsLeftInPlaceRatherThanRefused(): void
    {
        $reconciler = $this->createReconciler(
            groups: [$this->createGroup(self::GROUP_IDENTIFIER, self::GROUP_NAME)],
            enabledKeys: [
                $this->createKey(self::KEY_IDENTIFIER, self::KEY_NAME, enabled: true),
                $this->createKey('01OTHERKEY', self::KEY_NAME, enabled: true, id: 52),
            ]
        );

        $report = $reconciler->apply($this->createDocument(), $this->createStore(), false);

        $this->assertFalse(
            $report->hasConflicts(),
            implode(' ', $report->getNamed(ApplyOutcome::CONFLICT))
        );
        $this->assertSame([sprintf('key "%s"', self::KEY_NAME)], $report->getNamed(ApplyOutcome::TARGET_ONLY));
    }

    /**
     * A definition the file leaves out is stored as `[]`, so the apply after it has to read `[]` as
     * the absent definition it wrote rather than as a change. Without that, deploying one file twice
     * never becomes a no-op: every run reports the key updated and saves it again.
     */
    public function testAKeyWhoseDefinitionTheFileLeavesOutIsUnchangedOnTheApplyAfterIt(): void
    {
        $entry = $this->createKeyEntry(enabled: true);
        unset($entry[ArtifactField::DEFINITION->value]);

        $stored = $this->createKey(self::KEY_IDENTIFIER, self::KEY_NAME, enabled: true);
        // what the first apply wrote for a definition the file did not carry
        $stored->setDefinition('[]');

        $reconciler = $this->createReconciler(
            groups: [$this->createGroup(self::GROUP_IDENTIFIER, self::GROUP_NAME)],
            enabledKeys: [$stored],
            keyAssignments: [$this->createAssignment(self::GROUP_IDENTIFIER, self::KEY_IDENTIFIER)]
        );

        $report = $reconciler->apply(
            $this->createDocument(['keys' => [$entry]]),
            $this->createStore(),
            false
        );

        $this->assertSame(0, $report->getCount(ApplyOutcome::UPDATED));
        $this->assertSame(4, $report->getCount(ApplyOutcome::MATCHED_UNCHANGED));
        $this->assertSame([], $this->writes);
    }

    /**
     * A dry run decides an adoption without writing it, so the row it adopted still carries no
     * identifier while the run has already settled what its identifier is. A relation of the store
     * is matched through what the run resolved, not through the row - otherwise an assignment the
     * store already carries reads as target only and the file's entry for it as a creation.
     */
    public function testADryRunMatchesAnAssignmentWhoseEndpointItHasOnlyJustAdopted(): void
    {
        $reconciler = $this->createReconciler(
            groups: [$this->createGroup(null, self::GROUP_NAME)],
            enabledKeys: [$this->createKey(self::KEY_IDENTIFIER, self::KEY_NAME, enabled: true)],
            // the row's group identifier is still NULL, because the adoption has not been written
            keyAssignments: [$this->createAssignment('', self::KEY_IDENTIFIER)]
        );

        $report = $reconciler->apply($this->createDocument(), $this->createStore(), true);

        $this->assertSame(0, $report->getCount(ApplyOutcome::TARGET_ONLY));
        $this->assertSame(0, $report->getCount(ApplyOutcome::CREATED));
        $this->assertSame(1, $report->getCount(ApplyOutcome::ADOPTED));
        // the store, the key and the assignment between the adopted group and that key
        $this->assertSame(3, $report->getCount(ApplyOutcome::MATCHED_UNCHANGED));
    }

    /**
     * The same for the other relation kind.
     */
    public function testADryRunMatchesACollectionRelationWhoseEndpointItHasOnlyJustAdopted(): void
    {
        $document = $this->createDocument([
            'collections' => [
                ['identifier' => self::COLLECTION_IDENTIFIER, 'name' => 'Fasteners', 'description' => null],
            ],
            'collectionGroupRelations' => [
                [
                    'collection' => self::COLLECTION_IDENTIFIER,
                    'group' => self::GROUP_IDENTIFIER,
                    'sorter' => 1,
                ],
            ],
        ]);

        $reconciler = $this->createReconciler(
            groups: [$this->createGroup(self::GROUP_IDENTIFIER, self::GROUP_NAME)],
            collections: [$this->createCollection(null, 'Fasteners')],
            collectionRelations: [$this->createCollectionRelation('', self::GROUP_IDENTIFIER)]
        );

        $report = $reconciler->apply($document, $this->createStore(), true);

        $this->assertSame(0, $report->getCount(ApplyOutcome::TARGET_ONLY));
        $this->assertSame(1, $report->getCount(ApplyOutcome::ADOPTED));
        $this->assertContains(
            'collection group relation "01COLLECTION -> 01GROUP"',
            $report->getNamed(ApplyOutcome::MATCHED_UNCHANGED)
        );
    }

    /**
     * Groups before sub groups before relations is the order every writer of this family takes these
     * three tables in. An apply writes sub groups first, so it has to reach the group rows before
     * that write - otherwise it holds a sub group while waiting for a group that a concurrent sub
     * group deletion already holds, and InnoDB aborts one of the two.
     */
    public function testTheGroupsOfTheStoreAreHeldBeforeTheFirstSubGroupIsWritten(): void
    {
        $document = $this->createDocument([
            'subGroups' => [
                ['identifier' => self::SUB_GROUP_IDENTIFIER, 'name' => 'Dimensions', 'description' => null],
            ],
        ]);

        $this->createReconciler()->apply($document, $this->createStore(), false);

        $this->assertSame(sprintf('holdGroups:%d', self::STORE_ID), $this->steps[0] ?? null);
        $this->assertContains(sprintf('subGroup:%s:Dimensions', self::SUB_GROUP_IDENTIFIER), $this->steps);
    }

    /**
     * A dry run holds nothing: it writes nothing to protect, and it opens no transaction for a lock
     * to live in.
     */
    public function testADryRunHoldsNoGroups(): void
    {
        $this->createReconciler()->apply($this->createDocument(), $this->createStore(), true);

        $this->assertSame([], $this->steps);
    }

    /**
     * The probe that refuses a disable is read under a lock in a real run and then written under
     * that answer. A plain read answers from a view that can predate an assignment a competing
     * writer has already committed, and the disable would then drop the very row the refusal exists
     * to protect.
     */
    public function testTheProbeRefusingADisableIsHeldInARealRunAndNotInADryRun(): void
    {
        $document = $this->createDocument([
            'keys' => [$this->createKeyEntry(enabled: false)],
            'keyGroupRelations' => [],
        ]);

        $this->createReconciler(
            enabledKeys: [$this->createKey(self::KEY_IDENTIFIER, self::KEY_NAME, enabled: true)],
            groupNamesByKeyId: [self::GROUP_NAME]
        )->apply($document, $this->createStore(), false);

        $this->assertSame([true], $this->disableProbesHeld);

        $this->disableProbesHeld = [];

        $this->createReconciler(
            enabledKeys: [$this->createKey(self::KEY_IDENTIFIER, self::KEY_NAME, enabled: true)],
            groupNamesByKeyId: [self::GROUP_NAME]
        )->apply($document, $this->createStore(), true);

        $this->assertSame([false], $this->disableProbesHeld);
    }

    /**
     * The same shape one level up. A group whose name the file reuses under another identifier is a
     * genuine refusal - applying the file would have to write two rows under one name. A group that
     * merely shares a name with an entry the file's own identifier owner has already answered for
     * is not: it is the target-only row it is, and refusing it rolls the import back over nothing.
     */
    public function testAGroupSharingANameWithAnAlreadyAnsweredEntryIsLeftInPlace(): void
    {
        $reconciler = $this->createReconciler(
            groups: [
                $this->createGroup(self::GROUP_IDENTIFIER, self::GROUP_NAME),
                $this->createGroup('01OTHERGROUP', self::GROUP_NAME, id: 42),
            ]
        );

        $report = $reconciler->apply($this->createDocument(), $this->createStore(), false);

        $this->assertFalse(
            $report->hasConflicts(),
            implode(' ', $report->getNamed(ApplyOutcome::CONFLICT))
        );
        $this->assertSame([sprintf('group "%s"', self::GROUP_NAME)], $report->getNamed(ApplyOutcome::TARGET_ONLY));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createDocument(array $overrides = []): ArtifactDocument
    {
        $artifact = [
            'store' => [
                'identifier' => self::STORE_IDENTIFIER,
                'name' => 'ETIM',
                'description' => 'The ETIM classification',
            ],
            'subGroups' => [],
            'collections' => [],
            'groups' => [
                ['identifier' => self::GROUP_IDENTIFIER, 'name' => self::GROUP_NAME, 'description' => null],
            ],
            'keys' => [$this->createKeyEntry(enabled: true)],
            'collectionGroupRelations' => [],
            'keyGroupRelations' => [$this->createAssignmentEntry()],
        ];

        return (new ArtifactParser())->parse(
            json_encode([...$artifact, ...$overrides], JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function createKeyEntry(bool $enabled): array
    {
        return [
            'identifier' => self::KEY_IDENTIFIER,
            'name' => self::KEY_NAME,
            'title' => 'Weight',
            'description' => null,
            'type' => 'input',
            'definition' => ['fieldtype' => 'input', 'title' => 'Weight'],
            'enabled' => $enabled,
        ];
    }

    /**
     * The file's key entry stating the search index flag as the default its own assignments take.
     *
     * @return array<string, mixed>
     */
    private function createKeyEntryCarryingTheFlag(): array
    {
        $entry = $this->createKeyEntry(enabled: true);
        $entry['definition'][self::INDEXED_MEMBER] = false;

        return $entry;
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function createAssignmentEntry(array $overrides = []): array
    {
        return [
            'group' => self::GROUP_IDENTIFIER,
            'key' => self::KEY_IDENTIFIER,
            'sorter' => 1,
            'mandatory' => false,
            ...$overrides,
        ];
    }

    /**
     * @param GroupConfig[] $groups
     * @param SubGroupConfig[] $subGroups
     * @param CollectionConfig[] $collections
     * @param KeyConfig[] $enabledKeys
     * @param KeyConfig[] $disabledKeys
     * @param ScopedKeyAssignment[] $keyAssignments
     * @param ScopedCollectionRelation[] $collectionRelations
     * @param string[] $groupNamesByKeyId
     */
    private function createReconciler(
        array $groups = [],
        array $subGroups = [],
        array $collections = [],
        array $enabledKeys = [],
        array $disabledKeys = [],
        array $keyAssignments = [],
        array $collectionRelations = [],
        array $groupNamesByKeyId = [],
        ?Connection $connection = null,
        bool $storeNameIsTaken = false,
        bool $subGroupNameIsTaken = false,
    ): Reconciler {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('isNameTaken')->willReturn($storeNameIsTaken);
        $storeRepository->method('saveFromArtifact')->willReturnCallback(
            function (
                ?StoreConfig $config,
                string $identifier,
                string $name,
                ?string $description
            ): StoreConfig {
                $this->recordWrite(sprintf('store:%s:%s:%s', $identifier, $name, $description ?? ''));
                $saved = $config ?? $this->createStore();

                return $saved;
            }
        );

        $subGroupRepository = $this->createMock(SubGroupRepositoryInterface::class);
        $subGroupRepository->method('getChunkAfterId')->willReturnCallback(
            static fn (int $storeId, int $afterId, int $limit, ?bool $withIdentifier): array
                => self::inIdentifierPhase($subGroups, $withIdentifier)
        );
        $subGroupRepository->method('isNameTaken')->willReturn($subGroupNameIsTaken);
        $subGroupRepository->method('saveFromArtifact')->willReturnCallback(
            function (?SubGroupConfig $config, int $storeId, string $identifier, string $name): SubGroupConfig {
                $this->recordWrite(sprintf('subGroup:%s:%s', $identifier, $name));

                return $config ?? $this->createSubGroup($identifier, $name);
            }
        );

        $collectionRepository = $this->createMock(CollectionRepositoryInterface::class);
        $collectionRepository->method('getChunkAfterId')->willReturnCallback(
            static fn (int $storeId, int $afterId, int $limit, ?bool $withIdentifier): array
                => self::inIdentifierPhase($collections, $withIdentifier)
        );
        $collectionRepository->method('saveFromArtifact')->willReturnCallback(
            function (?CollectionConfig $config, int $storeId, string $identifier, string $name): CollectionConfig {
                $this->recordWrite(sprintf('collection:%s:%s', $identifier, $name));
                $saved = $config ?? new CollectionConfig();
                $saved->setId(31);

                return $saved;
            }
        );

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository->method('holdGroupsOfStore')->willReturnCallback(
            function (int $storeId): void {
                // not a write, so it is recorded in the step log only
                $this->steps[] = sprintf('holdGroups:%d', $storeId);
            }
        );
        $groupRepository->method('getChunkAfterId')->willReturnCallback(
            static fn (int $storeId, int $afterId, int $limit, ?bool $withIdentifier): array
                => self::inIdentifierPhase($groups, $withIdentifier)
        );
        $groupRepository->method('saveFromArtifact')->willReturnCallback(
            function (?GroupConfig $config, int $storeId, string $identifier, string $name): GroupConfig {
                $this->recordWrite(sprintf('group:%s:%s', $identifier, $name));
                $saved = $config ?? new GroupConfig();
                $saved->setId(self::GROUP_ID);

                return $saved;
            }
        );

        $keyRepository = $this->createMock(KeyRepositoryInterface::class);
        $keyRepository->method('getChunkAfterId')->willReturnCallback(
            static fn (
                int $storeId,
                bool $disabledOnly,
                int $afterId,
                int $limit,
                ?bool $withIdentifier
            ): array => self::inIdentifierPhase($disabledOnly ? $disabledKeys : $enabledKeys, $withIdentifier)
        );
        $keyRepository->method('saveFromArtifact')->willReturnCallback(
            function (?KeyConfig $config, int $storeId, ArtifactKeyEntry $entry): KeyConfig {
                $this->recordWrite(sprintf('key:%s:%s', $entry->getIdentifier(), $entry->getName()));
                $saved = $config ?? new KeyConfig();
                $saved->setId(self::KEY_ID);
                // the column the write leaves behind, encoded the way the repository encodes it
                $saved->setDefinition((string) json_encode($entry->getDefinition() ?? []));

                return $saved;
            }
        );
        $keyGroupRelationRepository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $keyGroupRelationRepository->method('getChunkAfter')->willReturn($keyAssignments);
        $keyGroupRelationRepository->method('getGroupNamesByKeyId')->willReturnCallback(
            function (int $keyId, bool $hold) use ($groupNamesByKeyId): array {
                $this->disableProbesHeld[] = $hold;

                return $groupNamesByKeyId;
            }
        );
        $keyGroupRelationRepository->method('createOrUpdateFromArtifact')->willReturnCallback(
            function (ArtifactAssignmentEntry $entry, int $keyId, int $groupId): KeyGroupRelation {
                $this->recordWrite(
                    sprintf(
                        'assignment:%s:%s',
                        $entry->getGroupIdentifier(),
                        $entry->getKeyIdentifier()
                    )
                );
                $mapper = new ArtifactEntryMapper(new InMemoryKeyLocalizationResolver());
                $relation = $mapper->mapToAssignment($entry, $keyId, $groupId);
                $this->savedAssignments[] = $relation;

                return $relation;
            }
        );

        $collectionRelationRepository = $this->createMock(CollRelationRepositoryInterface::class);
        $collectionRelationRepository->method('getChunkAfter')->willReturn($collectionRelations);
        $collectionRelationRepository->method('createOrUpdate')->willReturnCallback(
            function (int $colId, int $groupId, int $sorter): CollectionGroupRelation {
                $this->recordWrite(sprintf('collectionRelation:%d:%d:%d', $colId, $groupId, $sorter));

                $relation = new CollectionGroupRelation();
                $relation->setColId($colId);
                $relation->setGroupId($groupId);
                $relation->setSorter($sorter);

                return $relation;
            }
        );

        $parser = new ArtifactParser();
        $matcher = new ArtifactTargetMatcher(new InMemoryKeyLocalizationResolver());
        $diagnostics = new ApplyDiagnostics();
        $refusals = new ApplyRefusals(
            $collectionRepository,
            $groupRepository,
            $keyGroupRelationRepository,
            $subGroupRepository
        );

        return new Reconciler(
            $parser,
            $matcher,
            new ArtifactEntryWriter(
                $parser,
                $matcher,
                $diagnostics,
                $refusals,
                new ArtifactRelationWriter(
                    $parser,
                    $matcher,
                    $diagnostics,
                    $refusals,
                    $collectionRelationRepository,
                    $keyGroupRelationRepository
                ),
                $keyRepository,
                $this->makeEmpty(SearchIndexRemapTriggerInterface::class)
            ),
            $diagnostics,
            $collectionRepository,
            $collectionRelationRepository,
            $connection ?? $this->makeEmpty(Connection::class),
            $groupRepository,
            $keyGroupRelationRepository,
            $keyRepository,
            $storeRepository,
            $subGroupRepository
        );
    }

    /**
     * The rows of one identifier phase, the way the listing's own condition narrows them. An apply
     * walks the rows that carry an identifier before the rows that do not, so that the row owning
     * an entry's identifier claims it before an identifierless namesake can; a stub answering with
     * every row in both phases would reach each row twice and is not what the repository does.
     *
     * @param list<SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig> $rows
     *
     * @return list<SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig>
     */
    private static function inIdentifierPhase(array $rows, ?bool $withIdentifier): array
    {
        if ($withIdentifier === null) {
            return $rows;
        }

        return array_values(
            array_filter(
                $rows,
                static fn (object $row): bool => ($row->getIdentifier() !== null) === $withIdentifier
            )
        );
    }

    private function createStore(): StoreConfig
    {
        $store = new StoreConfig();
        $store->setId(self::STORE_ID);
        $store->setIdentifier(self::STORE_IDENTIFIER);
        $store->setName('ETIM');
        $store->setDescription('The ETIM classification');

        return $store;
    }

    private function createSubGroup(string $identifier, string $name): SubGroupConfig
    {
        $config = new SubGroupConfig();
        $config->setId(21);
        $config->setStoreId(self::STORE_ID);
        $config->setIdentifier($identifier);
        $config->setName($name);

        return $config;
    }

    private function createCollection(
        ?string $identifier,
        string $name,
        int $id = self::COLLECTION_ID,
    ): CollectionConfig {
        $config = new CollectionConfig();
        $config->setId($id);
        $config->setStoreId(self::STORE_ID);
        $config->setName($name);

        if ($identifier !== null) {
            $config->setIdentifier($identifier);
        }

        return $config;
    }

    private function createCollectionRelation(
        string $collectionIdentifier,
        string $groupIdentifier,
        int $sorter = 1,
    ): ScopedCollectionRelation {
        $relation = new CollectionGroupRelation();
        $relation->setColId(self::COLLECTION_ID);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setSorter($sorter);

        return new ScopedCollectionRelation($relation, $collectionIdentifier, $groupIdentifier);
    }

    private function createGroup(?string $identifier, string $name, int $id = self::GROUP_ID): GroupConfig
    {
        $config = new GroupConfig();
        $config->setId($id);
        $config->setStoreId(self::STORE_ID);
        $config->setName($name);

        if ($identifier !== null) {
            $config->setIdentifier($identifier);
        }

        return $config;
    }

    private function createKey(
        ?string $identifier,
        string $name,
        bool $enabled,
        int $id = self::KEY_ID,
    ): KeyConfig {
        $config = new KeyConfig();
        $config->setId($id);
        $config->setStoreId(self::STORE_ID);

        if ($identifier !== null) {
            $config->setIdentifier($identifier);
        }

        $config->setName($name);
        $config->setTitle('Weight');
        $config->setType('input');
        $config->setDefinition(self::KEY_DEFINITION);
        $config->setEnabled($enabled);

        return $config;
    }

    private function createAssignment(
        string $groupIdentifier,
        string $keyIdentifier,
        int $groupId = self::GROUP_ID,
        int $keyId = self::KEY_ID,
    ): ScopedKeyAssignment {
        $relation = new KeyGroupRelation();
        $relation->setGroupId($groupId);
        $relation->setKeyId($keyId);
        $relation->setSorter(1);
        $relation->setMandatory(false);

        return new ScopedKeyAssignment($relation, $groupIdentifier, $keyIdentifier);
    }

    private function recordWrite(string $write): void
    {
        $this->writes[] = $write;
        $this->steps[] = $write;
    }

    protected function _after(): void
    {
        $this->writes = [];
        $this->savedAssignments = [];
        $this->steps = [];
        $this->disableProbesHeld = [];
    }
}
