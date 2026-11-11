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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactBuilder;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryMapper;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedCollectionRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function array_column;
use function array_keys;
use function array_map;
use function implode;
use function json_decode;

/**
 * The artifact is one JSON document the builder writes a piece at a time. The assertions are made
 * on the document those pieces add up to, which is what a reader gets either way.
 *
 * @internal
 */
final class ArtifactBuilderTest extends Unit
{
    private const int STORE_ID = 1;

    private const string STORE_IDENTIFIER = '01JBQ8X9Z0STORESTORESTORE';

    private const string GROUP_IDENTIFIER = '01JBQ8X9Z0GROUPGROUPGROUP';

    private const string COLLECTION_IDENTIFIER = '01JBQ8X9Z0COLCOLCOLCOLCOL';

    private const string UNWRITTEN = '01JBQ8X9Z0NEVERWRITTEN';

    private const string KEY_IDENTIFIER = '01JA';

    /**
     * The order of the sections is the order an apply needs them in: every one of them resolves
     * against the sections before it.
     */
    public function testTheDocumentCarriesTheStoreAndTheSixSectionsInOrder(): void
    {
        $document = $this->buildDocument();

        $this->assertSame(
            [
                'store',
                'subGroups',
                'collections',
                'groups',
                'keys',
                'collectionGroupRelations',
                'keyGroupRelations',
            ],
            array_keys($document)
        );
    }

    /**
     * Neither the row ids nor the timestamps travel: they describe one installation rather than one
     * configuration.
     */
    public function testNoEntryCarriesARowIdOrATimestamp(): void
    {
        $document = $this->buildDocument();

        foreach (['subGroups', 'collections', 'groups', 'keys'] as $section) {
            foreach ($document[$section] as $entry) {
                $this->assertArrayNotHasKey('id', $entry, $section);
                $this->assertArrayNotHasKey('storeId', $entry, $section);
                $this->assertArrayNotHasKey('creationDate', $entry, $section);
                $this->assertArrayNotHasKey('modificationDate', $entry, $section);
            }
        }

        foreach ($document['keyGroupRelations'] as $entry) {
            $this->assertArrayNotHasKey('keyId', $entry);
            $this->assertArrayNotHasKey('groupId', $entry);
        }

        foreach ($document['collectionGroupRelations'] as $entry) {
            $this->assertArrayNotHasKey('colId', $entry);
            $this->assertArrayNotHasKey('groupId', $entry);
        }
    }

    /**
     * Every key of the store, enabled and disabled, in one ascending run of identifiers - the two
     * walks the full coverage needs are merged rather than appended, so the section is sorted
     * whichever state a key happens to be in.
     */
    public function testTheKeySectionIsEveryKeySortedByIdentifier(): void
    {
        $document = $this->buildDocument();

        $this->assertSame(
            ['01JA', '01JB', '01JC', '01JD'],
            array_column($document['keys'], 'identifier')
        );
        $this->assertSame(
            [true, false, true, false],
            array_column($document['keys'], 'enabled')
        );
    }

    /**
     * An identifier is `/^[A-Za-z0-9][A-Za-z0-9_-]*$/`, which permits one made of digits and an `E`.
     * PHP compares two such strings as numbers, so merging the two walks with `<=` would order them
     * by value rather than by the string order each walk already arrived in: `...2E10` reads as the
     * larger number and sorts first as a string. The section is sorted by identifier either way.
     */
    public function testKeyIdentifiersThatReadAsNumbersAreStillSortedAsStrings(): void
    {
        $document = $this->buildDocument(
            enabledKeys: ['00000000000000000000003E09'],
            disabledKeys: ['00000000000000000000002E10'],
            assignments: [$this->createKeyAssignment(key: '00000000000000000000003E09')]
        );

        $this->assertSame(
            ['00000000000000000000002E10', '00000000000000000000003E09'],
            array_column($document['keys'], 'identifier')
        );
        $this->assertSame([false, true], array_column($document['keys'], 'enabled'));
    }

    public function testAnAssignmentNamingAGroupThatWasNotWrittenFailsTheExport(): void
    {
        $message = $this->exportFailure(assignments: [$this->createKeyAssignment(self::UNWRITTEN)])['message'];

        $this->assertStringContainsString(
            'key assignment "' . self::UNWRITTEN . ' -> ' . self::KEY_IDENTIFIER . '"',
            $message
        );
        $this->assertStringContainsString('the group "' . self::UNWRITTEN . '"', $message);
        $this->assertStringContainsString('exists neither in the file nor in the store', $message);
        $this->assertStringContainsString('run the export again', $message);
    }

    public function testAnAssignmentNamingAKeyNeitherWalkReturnedFailsTheExport(): void
    {
        $message = $this->exportFailure(
            assignments: [$this->createKeyAssignment(self::GROUP_IDENTIFIER, self::UNWRITTEN)]
        )['message'];

        $this->assertStringContainsString('the key "' . self::UNWRITTEN . '"', $message);
    }

    public function testAnAssignmentToADisabledKeyIsConsistent(): void
    {
        $document = $this->buildDocument(assignments: [$this->createKeyAssignment(self::GROUP_IDENTIFIER, '01JB')]);

        $this->assertCount(1, $document['keyGroupRelations']);
    }

    public function testAnAssignmentNamingASubGroupThatWasNotWrittenFailsTheExport(): void
    {
        $message = $this->exportFailure(
            assignments: [$this->createKeyAssignment(subGroup: self::UNWRITTEN)]
        )['message'];

        $this->assertStringContainsString('the sub group "' . self::UNWRITTEN . '"', $message);
    }

    public function testACollectionRelationNamingAnUnwrittenCollectionOrGroupFailsTheExport(): void
    {
        $collection = $this->exportFailure(
            collectionRelations: [$this->createCollectionRelation(self::UNWRITTEN)]
        )['message'];
        $group = $this->exportFailure(
            collectionRelations: [$this->createCollectionRelation(self::COLLECTION_IDENTIFIER, self::UNWRITTEN)]
        )['message'];

        $this->assertStringContainsString('collection group relation', $collection);
        $this->assertStringContainsString('the collection "' . self::UNWRITTEN . '"', $collection);
        $this->assertStringContainsString('the group "' . self::UNWRITTEN . '"', $group);
    }

    /**
     * The export stops at the first unresolvable entry: it is not written, nothing follows it, and the
     * relation is not read again to find out.
     */
    public function testTheExportStopsAtTheFirstUnresolvableEntryWithoutReadingAgain(): void
    {
        $calls = 0;
        $failure = $this->exportFailure(
            assignments: [
                $this->createKeyAssignment(self::UNWRITTEN),
                $this->createKeyAssignment(),
                $this->createKeyAssignment(),
            ],
            onAssignmentRead: static function () use (&$calls): void {
                ++$calls;
            }
        );
        $written = implode('', $failure['pieces']);

        $this->assertSame(1, $calls);
        $this->assertStringNotContainsString(self::UNWRITTEN, $written);
        $this->assertStringEndsWith('"keyGroupRelations":[', $written);
    }

    /**
     * What is written before the failure is neither JSON nor an artifact the apply side accepts.
     */
    public function testWhatWasWrittenBeforeTheFailureCannotBeApplied(): void
    {
        $failure = $this->exportFailure(assignments: [$this->createKeyAssignment(self::UNWRITTEN)]);
        $written = implode('', $failure['pieces']);

        $this->assertNull(json_decode($written));

        $this->expectException(InvalidArgumentException::class);
        (new ArtifactParser())->parse($written);
    }

    /**
     * A NULL identifier is not an identifier: the empty string a relation's join coerces it to must not
     * be taken for the entity that carries none.
     */
    public function testAnEntityWithoutAnIdentifierDoesNotSatisfyARelation(): void
    {
        $group = new GroupConfig();
        $group->setId(41);
        $group->setStoreId(self::STORE_ID);
        $group->setName('Measurements');

        $message = $this->exportFailure(
            groups: [$group],
            assignments: [$this->createKeyAssignment('')],
            collectionRelations: []
        )['message'];

        $this->assertStringContainsString('the group ""', $message);
    }

    /**
     * @param list<ScopedKeyAssignment>|null $assignments
     * @param list<ScopedCollectionRelation>|null $collectionRelations
     * @param list<GroupConfig>|null $groups
     *
     * @return array{message: string, pieces: list<string>}
     */
    private function exportFailure(
        ?array $assignments = null,
        ?array $collectionRelations = null,
        ?array $groups = null,
        ?callable $onAssignmentRead = null,
    ): array {
        $pieces = [];
        $builder = $this->createBuilder(
            ['01JA', '01JC'],
            ['01JB', '01JD'],
            $assignments,
            $collectionRelations,
            $groups,
            $onAssignmentRead
        );

        try {
            foreach ($builder->stream($this->createStore()) as $piece) {
                $pieces[] = $piece;
            }
        } catch (ConflictException $e) {
            return ['message' => $e->getMessage(), 'pieces' => $pieces];
        }

        $this->fail('The export was expected to fail.');
    }

    /**
     * @param list<string> $enabledKeys
     * @param list<string> $disabledKeys
     * @param list<ScopedKeyAssignment>|null $assignments
     *
     * @return array<string, mixed>
     */
    private function buildDocument(
        ?array $enabledKeys = null,
        ?array $disabledKeys = null,
        ?array $assignments = null
    ): array {
        $pieces = [];
        $builder = $this->createBuilder(
            $enabledKeys ?? ['01JA', '01JC'],
            $disabledKeys ?? ['01JB', '01JD'],
            $assignments
        );

        foreach ($builder->stream($this->createStore()) as $piece) {
            $pieces[] = $piece;
        }

        return json_decode(implode('', $pieces), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param list<string> $enabledKeys
     * @param list<string> $disabledKeys
     * @param list<ScopedKeyAssignment>|null $assignments
     * @param list<ScopedCollectionRelation>|null $collectionRelations
     * @param list<GroupConfig>|null $groups
     */
    private function createBuilder(
        array $enabledKeys,
        array $disabledKeys,
        ?array $assignments = null,
        ?array $collectionRelations = null,
        ?array $groups = null,
        ?callable $onAssignmentRead = null,
    ): ArtifactBuilder {
        $subGroupRepository = $this->createMock(SubGroupRepositoryInterface::class);
        $subGroupRepository->method('getChunkAfterIdentifier')->willReturn([$this->createSubGroup()]);

        $collectionRepository = $this->createMock(CollectionRepositoryInterface::class);
        $collectionRepository->method('getChunkAfterIdentifier')->willReturn([$this->createCollection()]);

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository->method('getChunkAfterIdentifier')->willReturn($groups ?? [$this->createGroup()]);

        // the enabled walk and the disabled walk, each already sorted by identifier
        $keyRepository = $this->createMock(KeyRepositoryInterface::class);
        $keyRepository
            ->method('getChunkAfterIdentifier')
            ->willReturnCallback(
                fn (int $storeId, bool $disabledOnly): array => array_map(
                    fn (string $identifier): KeyConfig => $this->createKey($identifier, !$disabledOnly),
                    $disabledOnly ? $disabledKeys : $enabledKeys
                )
            );

        $collectionRelationRepository = $this->createMock(CollRelationRepositoryInterface::class);
        $collectionRelationRepository
            ->method('getChunkAfter')
            ->willReturn($collectionRelations ?? [$this->createCollectionRelation()]);

        $keyGroupRelationRepository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $keyGroupRelationRepository
            ->method('getChunkAfter')
            ->willReturnCallback(
                function () use ($assignments, $onAssignmentRead): array {
                    if ($onAssignmentRead !== null) {
                        $onAssignmentRead();
                    }

                    return $assignments ?? [$this->createKeyAssignment()];
                }
            );

        return new ArtifactBuilder(
            new ArtifactEntryMapper(new InMemoryKeyLocalizationResolver()),
            $collectionRepository,
            $collectionRelationRepository,
            $groupRepository,
            $keyGroupRelationRepository,
            $keyRepository,
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

    private function createSubGroup(): SubGroupConfig
    {
        $config = new SubGroupConfig();
        $config->setId(21);
        $config->setStoreId(self::STORE_ID);
        $config->setIdentifier('01JBQ8X9Z0SUBSUBSUBSUBSUB');
        $config->setName('Dimensions');
        $config->setCreationDate(1734567890);

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

    private function createKey(string $identifier, bool $enabled): KeyConfig
    {
        $config = new KeyConfig();
        $config->setId(51);
        $config->setStoreId(self::STORE_ID);
        $config->setIdentifier($identifier);
        $config->setName('weight');
        $config->setType('input');
        $config->setDefinition('{"fieldtype":"input"}');
        $config->setEnabled($enabled);
        $config->setCreationDate(1734567890);

        return $config;
    }

    private function createCollectionRelation(
        string $collection = self::COLLECTION_IDENTIFIER,
        string $group = self::GROUP_IDENTIFIER
    ): ScopedCollectionRelation {
        $relation = new CollectionGroupRelation();
        $relation->setColId(31);
        $relation->setGroupId(41);
        $relation->setSorter(0);

        return new ScopedCollectionRelation($relation, $collection, $group);
    }

    private function createKeyAssignment(
        string $group = self::GROUP_IDENTIFIER,
        string $key = self::KEY_IDENTIFIER,
        ?string $subGroup = null
    ): ScopedKeyAssignment {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(51);
        $relation->setGroupId(41);
        $relation->setSorter(0);
        $relation->setMandatory(false);
        $relation->setMetadata(null);
        $relation->setDefinitionOverride(null);
        $relation->setSubGroup($subGroup);

        return new ScopedKeyAssignment($relation, $group, $key);
    }
}
