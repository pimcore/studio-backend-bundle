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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactAssignmentEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactDocument;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryMapper;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactKeyEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\Reconciler;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
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

    private const string STORE_IDENTIFIER = '01STORE';

    private const string GROUP_IDENTIFIER = '01GROUP';

    private const string KEY_IDENTIFIER = '01KEY';

    private const string GROUP_NAME = 'Measurements';

    private const string KEY_NAME = 'weight';

    private const string GROUP_WRITE = 'group:%s:%s';

    /**
     * @var list<string>
     */
    private array $writes = [];

    /**
     * @var list<KeyGroupRelation>
     */
    private array $savedAssignments = [];

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
     * @param string[] $groupNamesByKeyId
     */
    private function createReconciler(
        array $groups = [],
        array $subGroups = [],
        array $collections = [],
        array $enabledKeys = [],
        array $disabledKeys = [],
        array $keyAssignments = [],
        array $groupNamesByKeyId = [],
        ?Connection $connection = null,
    ): Reconciler {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('saveFromArtifact')->willReturnCallback(
            function (
                ?StoreConfig $config,
                string $identifier,
                string $name,
                ?string $description
            ): StoreConfig {
                $this->writes[] = sprintf('store:%s:%s:%s', $identifier, $name, $description ?? '');
                $saved = $config ?? $this->createStore();

                return $saved;
            }
        );

        $subGroupRepository = $this->createMock(SubGroupRepositoryInterface::class);
        $subGroupRepository->method('getChunkAfterId')->willReturn($subGroups);
        $subGroupRepository->method('saveFromArtifact')->willReturnCallback(
            function (?SubGroupConfig $config, int $storeId, string $identifier, string $name): SubGroupConfig {
                $this->writes[] = sprintf('subGroup:%s:%s', $identifier, $name);

                return $config ?? $this->createSubGroup($identifier, $name);
            }
        );

        $collectionRepository = $this->createMock(CollectionRepositoryInterface::class);
        $collectionRepository->method('getChunkAfterId')->willReturn($collections);
        $collectionRepository->method('saveFromArtifact')->willReturnCallback(
            function (?CollectionConfig $config, int $storeId, string $identifier, string $name): CollectionConfig {
                $this->writes[] = sprintf('collection:%s:%s', $identifier, $name);
                $saved = $config ?? new CollectionConfig();
                $saved->setId(31);

                return $saved;
            }
        );

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository->method('getChunkAfterId')->willReturn($groups);
        $groupRepository->method('saveFromArtifact')->willReturnCallback(
            function (?GroupConfig $config, int $storeId, string $identifier, string $name): GroupConfig {
                $this->writes[] = sprintf('group:%s:%s', $identifier, $name);
                $saved = $config ?? new GroupConfig();
                $saved->setId(self::GROUP_ID);

                return $saved;
            }
        );

        $keyRepository = $this->createMock(KeyRepositoryInterface::class);
        $keyRepository->method('getChunkAfterId')->willReturnCallback(
            static fn (int $storeId, bool $disabledOnly): array => $disabledOnly ? $disabledKeys : $enabledKeys
        );
        $keyRepository->method('saveFromArtifact')->willReturnCallback(
            function (?KeyConfig $config, int $storeId, ArtifactKeyEntry $entry): KeyConfig {
                $this->writes[] = sprintf('key:%s:%s', $entry->getIdentifier(), $entry->getName());
                $saved = $config ?? new KeyConfig();
                $saved->setId(self::KEY_ID);

                return $saved;
            }
        );

        $keyGroupRelationRepository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $keyGroupRelationRepository->method('getChunkByStore')->willReturn($keyAssignments);
        $keyGroupRelationRepository->method('getGroupNamesByKeyId')->willReturn($groupNamesByKeyId);
        $keyGroupRelationRepository->method('createOrUpdateFromArtifact')->willReturnCallback(
            function (ArtifactAssignmentEntry $entry, int $keyId, int $groupId): KeyGroupRelation {
                $this->writes[] = sprintf(
                    'assignment:%s:%s',
                    $entry->getGroupIdentifier(),
                    $entry->getKeyIdentifier()
                );
                $relation = (new ArtifactEntryMapper())->mapToAssignment($entry, $keyId, $groupId);
                $this->savedAssignments[] = $relation;

                return $relation;
            }
        );

        $collectionRelationRepository = $this->createMock(CollRelationRepositoryInterface::class);
        $collectionRelationRepository->method('getChunkByStore')->willReturn([]);

        return new Reconciler(
            new ArtifactParser(),
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

    private function createKey(string $identifier, string $name, bool $enabled): KeyConfig
    {
        $config = new KeyConfig();
        $config->setId(self::KEY_ID);
        $config->setStoreId(self::STORE_ID);
        $config->setIdentifier($identifier);
        $config->setName($name);
        $config->setTitle('Weight');
        $config->setType('input');
        $config->setDefinition('{"fieldtype":"input","title":"Weight"}');
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

    protected function _after(): void
    {
        $this->writes = [];
        $this->savedAssignments = [];
    }
}
