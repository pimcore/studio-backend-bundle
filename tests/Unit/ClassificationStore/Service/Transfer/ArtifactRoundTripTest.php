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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyReport;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactAssignmentEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactBuilder;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryMapper;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryWriter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactKeyEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParserInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactRelationWriter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactTargetMatcher;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\Reconciler;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedCollectionRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function implode;
use function json_encode;

/**
 * Export and apply are the two halves of one format, and the only way they can disagree is if each
 * is checked on its own. Both run over the same fixture here: the builder writes a store, the
 * parser reads what it wrote, and the reconciler applies it to an empty installation - so the
 * assertions are made on the values that arrive at the far end.
 *
 * The unit suite has no database, so what "arrives" is the entry each repository write was asked
 * for. That is the whole of what a real apply would persist.
 *
 * @internal
 */
final class ArtifactRoundTripTest extends Unit
{
    private const int STORE_ID = 1;

    private const string STORE_IDENTIFIER = '01STORE';

    private const string SUB_GROUP_IDENTIFIER = '01SUB';

    private const string COLLECTION_IDENTIFIER = '01COLL';

    private const string GROUP_IDENTIFIER = '01GROUP';

    private const string ENABLED_KEY_IDENTIFIER = '01KEYA';

    private const string DISABLED_KEY_IDENTIFIER = '01KEYB';

    private const string INDEXED_MEMBER = 'indexed';

    private const array FIXTURE_METADATA = ['source' => 'etim'];

    /**
     * @var array<string, ArtifactKeyEntry>
     */
    private array $appliedKeys = [];

    /**
     * @var array<string, ArtifactAssignmentEntry>
     */
    private array $appliedAssignments = [];

    /**
     * @var list<string>
     */
    private array $appliedEntities = [];

    /**
     * @var array<string, int>
     */
    private array $appliedCollectionRelations = [];

    /**
     * Exporting a store and applying the file to an empty installation reproduces the store: the
     * same entities, the same assignments, and the same per assignment values.
     */
    public function testApplyingAnExportToAnEmptyInstallationReproducesTheStore(): void
    {
        $report = $this->applyExportOf($this->createFixtureKeys());

        $this->assertSame(
            [
                'subGroup:' . self::SUB_GROUP_IDENTIFIER . ':Dimensions',
                'collection:' . self::COLLECTION_IDENTIFIER . ':Lighting',
                'group:' . self::GROUP_IDENTIFIER . ':Measurements',
            ],
            $this->appliedEntities
        );

        $this->assertSame([self::COLLECTION_IDENTIFIER . '->' . self::GROUP_IDENTIFIER => 7],
            $this->appliedCollectionRelations);

        $enabled = $this->appliedKeys[self::ENABLED_KEY_IDENTIFIER];
        $this->assertSame('weight', $enabled->getName());
        $this->assertSame('Weight', $enabled->getTitle());
        $this->assertSame('input', $enabled->getType());
        $this->assertSame(['fieldtype' => 'input', 'title' => 'Weight'], $enabled->getDefinition());
        $this->assertTrue($enabled->isEnabled());

        $assignment = $this->appliedAssignments[self::GROUP_IDENTIFIER . '->' . self::ENABLED_KEY_IDENTIFIER];
        $this->assertSame(3, $assignment->getSorter());
        $this->assertTrue($assignment->isMandatory());
        $this->assertSame(['source' => 'etim'], $assignment->getMetadata());
        $this->assertSame(['defaultUnit' => 'kg'], $assignment->getDefinitionOverride());
        $this->assertSame(self::SUB_GROUP_IDENTIFIER, $assignment->getSubGroup());

        $this->assertFalse($report->hasConflicts());
    }

    /**
     * A key the source has disabled is disabled in the target as well, flag and all.
     */
    public function testADisabledKeyArrivesDisabled(): void
    {
        $this->applyExportOf($this->createFixtureKeys());

        $disabled = $this->appliedKeys[self::DISABLED_KEY_IDENTIFIER];

        $this->assertSame('depth', $disabled->getName());
        $this->assertFalse($disabled->isEnabled());
    }

    /**
     * Renaming a key in the source and re-applying updates the key the identifier names rather than
     * creating a second one - which is the whole point of carrying the identifier.
     */
    public function testARenamedKeyUpdatesTheKeyItsIdentifierNamesRatherThanCreatingASecondOne(): void
    {
        $renamed = $this->createKey(self::ENABLED_KEY_IDENTIFIER, 'mass', enabled: true);

        $report = $this->applyExportOf(
            [$renamed, $this->createKey(self::DISABLED_KEY_IDENTIFIER, 'depth', enabled: false)],
            // the target already holds what the first apply produced, under the old name
            targetKeys: [$this->createKey(self::ENABLED_KEY_IDENTIFIER, 'weight', enabled: true)]
        );

        $this->assertSame(1, $report->getCount(ApplyOutcome::UPDATED));
        $this->assertSame('mass', $this->appliedKeys[self::ENABLED_KEY_IDENTIFIER]->getName());
        $this->assertSame(
            ['key "mass"'],
            $report->getNamed(ApplyOutcome::UPDATED),
            'The renamed key is the one that was updated.'
        );
    }

    /**
     * Applying the very same export twice over a target that already holds it writes nothing.
     */
    public function testApplyingTheSameExportTwiceDecidesNothing(): void
    {
        $keys = $this->createFixtureKeys();

        $report = $this->applyExportOf($keys, targetKeys: $keys, targetIsTheSource: true);

        $this->assertSame(0, $report->getCount(ApplyOutcome::CREATED));
        $this->assertSame(0, $report->getCount(ApplyOutcome::UPDATED));
        $this->assertSame(0, $report->getCount(ApplyOutcome::ADOPTED));
        $this->assertSame([], $this->appliedEntities);
        $this->assertSame([], $this->appliedKeys);
        $this->assertSame([], $this->appliedAssignments);
    }

    /**
     * The export of a store whose assignment column is NULL writes `"metadata": null`, and an apply
     * of that file leaves the column NULL even under a key whose own definition excludes it from the
     * search index: nothing is seeded from the key, so the export of the result writes the same
     * `null` again and the round trip is stable rather than one that keeps inventing a member.
     */
    public function testAnAssignmentCreatedFromAFileSeedsNothingFromItsKeyAndExportsTheSameNull(): void
    {
        $keys = $this->createFixtureKeys(notIndexed: true);

        $report = $this->applyExportOf($keys, assignmentMetadata: null);

        $applied = $this->appliedAssignments[self::GROUP_IDENTIFIER . '->' . self::ENABLED_KEY_IDENTIFIER];
        $this->assertNull($applied->getMetadata());
        $this->assertFalse($report->hasConflicts());

        // and the export of what the apply produced writes the same null back
        $exported = (new ArtifactParser())->parse($this->export($keys, $applied->getMetadata()));
        $entryKey = self::GROUP_IDENTIFIER
            . ArtifactParserInterface::ENTRY_KEY_SEPARATOR
            . self::ENABLED_KEY_IDENTIFIER;

        $this->assertNull(
            $exported->getEntries(ArtifactSection::KEY_GROUP_RELATIONS)[$entryKey]['metadata']
        );
    }

    /**
     * @param KeyConfig[] $sourceKeys
     * @param KeyConfig[] $targetKeys
     * @param array<string, mixed>|null $assignmentMetadata
     */
    private function applyExportOf(
        array $sourceKeys,
        array $targetKeys = [],
        bool $targetIsTheSource = false,
        bool $keysAreNotIndexed = false,
        ?array $assignmentMetadata = self::FIXTURE_METADATA,
    ): ApplyReport {
        $json = $this->export($sourceKeys, $assignmentMetadata);
        $document = (new ArtifactParser())->parse($json);

        return $this
            ->createReconciler($targetKeys, $targetIsTheSource, $keysAreNotIndexed)
            ->apply($document, $this->createStore(), false);
    }

    /**
     * @param KeyConfig[] $keys
     * @param array<string, mixed>|null $assignmentMetadata
     */
    private function export(array $keys, ?array $assignmentMetadata = self::FIXTURE_METADATA): string
    {
        $enabled = [];
        $disabled = [];

        foreach ($keys as $key) {
            if ($key->getEnabled()) {
                $enabled[] = $key;

                continue;
            }

            $disabled[] = $key;
        }

        $subGroupRepository = $this->createMock(SubGroupRepositoryInterface::class);
        $subGroupRepository->method('getChunkAfterIdentifier')->willReturn([$this->createSubGroup()]);

        $collectionRepository = $this->createMock(CollectionRepositoryInterface::class);
        $collectionRepository->method('getChunkAfterIdentifier')->willReturn([$this->createCollection()]);

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository->method('getChunkAfterIdentifier')->willReturn([$this->createGroup()]);

        $keyRepository = $this->createMock(KeyRepositoryInterface::class);
        $keyRepository->method('getChunkAfterIdentifier')->willReturnCallback(
            static fn (int $storeId, bool $disabledOnly): array => $disabledOnly ? $disabled : $enabled
        );

        $collectionRelationRepository = $this->createMock(CollRelationRepositoryInterface::class);
        $collectionRelationRepository
            ->method('getChunkAfter')
            ->willReturn([$this->createCollectionRelation()]);

        $keyGroupRelationRepository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $keyGroupRelationRepository
            ->method('getChunkAfter')
            ->willReturn([$this->createAssignment($assignmentMetadata)]);

        $builder = new ArtifactBuilder(
            new ArtifactEntryMapper(),
            $collectionRepository,
            $collectionRelationRepository,
            $groupRepository,
            $keyGroupRelationRepository,
            $keyRepository,
            $subGroupRepository
        );

        $pieces = [];
        foreach ($builder->stream($this->createStore()) as $piece) {
            $pieces[] = $piece;
        }

        return implode('', $pieces);
    }

    /**
     * @param KeyConfig[] $targetKeys
     */
    private function createReconciler(
        array $targetKeys,
        bool $targetIsTheSource,
        bool $keysAreNotIndexed = false,
    ): Reconciler {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('saveFromArtifact')->willReturnCallback(
            function (?StoreConfig $config): StoreConfig {
                $this->appliedEntities[] = 'store';

                return $config ?? $this->createStore();
            }
        );

        $subGroupRepository = $this->createMock(SubGroupRepositoryInterface::class);
        $subGroupRepository
            ->method('getChunkAfterId')
            ->willReturnCallback(
                fn (int $storeId, int $afterId, int $limit, ?bool $withIdentifier): array
                    => $targetIsTheSource && $withIdentifier !== false ? [$this->createSubGroup()] : []
            );
        $subGroupRepository->method('saveFromArtifact')->willReturnCallback(
            function (?SubGroupConfig $config, int $storeId, string $identifier, string $name): SubGroupConfig {
                $this->appliedEntities[] = 'subGroup:' . $identifier . ':' . $name;

                return $config ?? $this->createSubGroup();
            }
        );

        $collectionRepository = $this->createMock(CollectionRepositoryInterface::class);
        $collectionRepository
            ->method('getChunkAfterId')
            ->willReturnCallback(
                fn (int $storeId, int $afterId, int $limit, ?bool $withIdentifier): array
                    => $targetIsTheSource && $withIdentifier !== false ? [$this->createCollection()] : []
            );
        $collectionRepository->method('saveFromArtifact')->willReturnCallback(
            function (?CollectionConfig $config, int $storeId, string $identifier, string $name): CollectionConfig {
                $this->appliedEntities[] = 'collection:' . $identifier . ':' . $name;

                return $config ?? $this->createCollection();
            }
        );

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository
            ->method('getChunkAfterId')
            ->willReturnCallback(
                fn (int $storeId, int $afterId, int $limit, ?bool $withIdentifier): array
                    => $targetIsTheSource && $withIdentifier !== false ? [$this->createGroup()] : []
            );
        $groupRepository->method('saveFromArtifact')->willReturnCallback(
            function (?GroupConfig $config, int $storeId, string $identifier, string $name): GroupConfig {
                $this->appliedEntities[] = 'group:' . $identifier . ':' . $name;

                return $config ?? $this->createGroup();
            }
        );

        $keyRepository = $this->createMock(KeyRepositoryInterface::class);
        $keyRepository->method('getChunkAfterId')->willReturnCallback(
            static function (
                int $storeId,
                bool $disabledOnly,
                int $afterId,
                int $limit,
                ?bool $withIdentifier
            ) use ($targetKeys): array {
                $matching = [];
                foreach ($targetKeys as $key) {
                    // the stub narrows by the identifier phase the way the listing's condition does
                    if ($key->getEnabled() !== $disabledOnly
                        && ($withIdentifier === null || ($key->getIdentifier() !== null) === $withIdentifier)) {
                        $matching[] = $key;
                    }
                }

                return $matching;
            }
        );
        $keyRepository->method('saveFromArtifact')->willReturnCallback(
            function (?KeyConfig $config, int $storeId, ArtifactKeyEntry $entry): KeyConfig {
                $this->appliedKeys[$entry->getIdentifier()] = $entry;

                return $config ?? $this->createKey($entry->getIdentifier(), $entry->getName(), true);
            }
        );
        // the key a created assignment reads its search index flag default from, as the apply left it
        $keyRepository->method('getById')->willReturnCallback(
            fn (int $id): KeyConfig => $this->createKey(
                $id === 52 ? self::DISABLED_KEY_IDENTIFIER : self::ENABLED_KEY_IDENTIFIER,
                $id === 52 ? 'depth' : 'weight',
                enabled: true,
                notIndexed: $keysAreNotIndexed
            )
        );

        $keyGroupRelationRepository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $keyGroupRelationRepository
            ->method('getChunkAfter')
            ->willReturn($targetIsTheSource ? [$this->createAssignment()] : []);
        $keyGroupRelationRepository->method('getGroupNamesByKeyId')->willReturn([]);
        $keyGroupRelationRepository->method('createOrUpdateFromArtifact')->willReturnCallback(
            function (ArtifactAssignmentEntry $entry, int $keyId, int $groupId): KeyGroupRelation {
                $key = $entry->getGroupIdentifier() . '->' . $entry->getKeyIdentifier();
                $this->appliedAssignments[$key] = $entry;

                return (new ArtifactEntryMapper())->mapToAssignment($entry, $keyId, $groupId);
            }
        );

        $collectionRelationRepository = $this->createMock(CollRelationRepositoryInterface::class);
        $collectionRelationRepository
            ->method('getChunkAfter')
            ->willReturn($targetIsTheSource ? [$this->createCollectionRelation()] : []);
        $collectionRelationRepository->method('createOrUpdate')->willReturnCallback(
            function (int $colId, int $groupId, int $sorter): CollectionGroupRelation {
                $this->appliedCollectionRelations[self::COLLECTION_IDENTIFIER . '->' . self::GROUP_IDENTIFIER]
                    = $sorter;

                return $this->createCollectionRelation()->getRelation();
            }
        );

        $parser = new ArtifactParser();
        $matcher = new ArtifactTargetMatcher();
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
            $this->makeEmpty(Connection::class),
            $groupRepository,
            $keyGroupRelationRepository,
            $keyRepository,
            $storeRepository,
            $subGroupRepository
        );
    }

    /**
     * @return KeyConfig[]
     */
    private function createFixtureKeys(bool $notIndexed = false): array
    {
        return [
            $this->createKey(self::ENABLED_KEY_IDENTIFIER, 'weight', enabled: true, notIndexed: $notIndexed),
            $this->createKey(self::DISABLED_KEY_IDENTIFIER, 'depth', enabled: false, notIndexed: $notIndexed),
        ];
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

    private function createSubGroup(): SubGroupConfig
    {
        $config = new SubGroupConfig();
        $config->setId(21);
        $config->setStoreId(self::STORE_ID);
        $config->setIdentifier(self::SUB_GROUP_IDENTIFIER);
        $config->setName('Dimensions');

        return $config;
    }

    private function createCollection(): CollectionConfig
    {
        $config = new CollectionConfig();
        $config->setId(31);
        $config->setStoreId(self::STORE_ID);
        $config->setIdentifier(self::COLLECTION_IDENTIFIER);
        $config->setName('Lighting');

        return $config;
    }

    private function createGroup(): GroupConfig
    {
        $config = new GroupConfig();
        $config->setId(41);
        $config->setStoreId(self::STORE_ID);
        $config->setIdentifier(self::GROUP_IDENTIFIER);
        $config->setName('Measurements');

        return $config;
    }

    private function createKey(
        string $identifier,
        string $name,
        bool $enabled,
        bool $notIndexed = false,
    ): KeyConfig {
        $title = $name === 'depth' ? 'Depth' : 'Weight';
        $definition = ['fieldtype' => 'input', 'title' => $title];

        if ($notIndexed) {
            $definition[self::INDEXED_MEMBER] = false;
        }

        $config = new KeyConfig();
        $config->setId($identifier === self::ENABLED_KEY_IDENTIFIER ? 51 : 52);
        $config->setStoreId(self::STORE_ID);
        $config->setIdentifier($identifier);
        $config->setName($name);
        $config->setTitle($title);
        $config->setType('input');
        $config->setDefinition((string) json_encode($definition));
        $config->setEnabled($enabled);

        return $config;
    }

    private function createCollectionRelation(): ScopedCollectionRelation
    {
        $relation = new CollectionGroupRelation();
        $relation->setColId(31);
        $relation->setGroupId(41);
        $relation->setSorter(7);

        return new ScopedCollectionRelation($relation, self::COLLECTION_IDENTIFIER, self::GROUP_IDENTIFIER);
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    private function createAssignment(?array $metadata = self::FIXTURE_METADATA): ScopedKeyAssignment
    {
        $relation = new KeyGroupRelation();
        $relation->setGroupId(41);
        $relation->setKeyId(51);
        $relation->setSorter(3);
        $relation->setMandatory(true);
        $relation->setMetadata($metadata);
        $relation->setDefinitionOverride(['defaultUnit' => 'kg']);
        $relation->setSubGroup(self::SUB_GROUP_IDENTIFIER);

        return new ScopedKeyAssignment($relation, self::GROUP_IDENTIFIER, self::ENABLED_KEY_IDENTIFIER);
    }

    protected function _after(): void
    {
        $this->appliedKeys = [];
        $this->appliedAssignments = [];
        $this->appliedEntities = [];
        $this->appliedCollectionRelations = [];
    }
}
