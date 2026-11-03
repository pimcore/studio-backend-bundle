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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedCollectionRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function array_column;
use function array_keys;
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
     * @return array<string, mixed>
     */
    private function buildDocument(): array
    {
        $pieces = [];
        foreach ($this->createBuilder()->stream($this->createStore()) as $piece) {
            $pieces[] = $piece;
        }

        return json_decode(implode('', $pieces), true, 512, JSON_THROW_ON_ERROR);
    }

    private function createBuilder(): ArtifactBuilder
    {
        $subGroupRepository = $this->createMock(SubGroupRepositoryInterface::class);
        $subGroupRepository->method('getChunkByStore')->willReturn([$this->createSubGroup()]);

        $collectionRepository = $this->createMock(CollectionRepositoryInterface::class);
        $collectionRepository->method('getChunkByStore')->willReturn([$this->createCollection()]);

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository->method('getChunkByStore')->willReturn([$this->createGroup()]);

        // the enabled walk and the disabled walk, each already sorted by identifier
        $keyRepository = $this->createMock(KeyRepositoryInterface::class);
        $keyRepository
            ->method('getChunkByStore')
            ->willReturnCallback(
                fn (int $storeId, bool $disabledOnly): array => $disabledOnly
                    ? [$this->createKey('01JB', false), $this->createKey('01JD', false)]
                    : [$this->createKey('01JA', true), $this->createKey('01JC', true)]
            );

        $collectionRelationRepository = $this->createMock(CollRelationRepositoryInterface::class);
        $collectionRelationRepository
            ->method('getChunkByStore')
            ->willReturn([$this->createCollectionRelation()]);

        $keyGroupRelationRepository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $keyGroupRelationRepository->method('getChunkByStore')->willReturn([$this->createKeyAssignment()]);

        return new ArtifactBuilder(
            new ArtifactEntryMapper(),
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

    private function createCollectionRelation(): ScopedCollectionRelation
    {
        $relation = new CollectionGroupRelation();
        $relation->setColId(31);
        $relation->setGroupId(41);
        $relation->setSorter(0);

        return new ScopedCollectionRelation($relation, self::COLLECTION_IDENTIFIER, self::GROUP_IDENTIFIER);
    }

    private function createKeyAssignment(): ScopedKeyAssignment
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(51);
        $relation->setGroupId(41);
        $relation->setSorter(0);
        $relation->setMandatory(false);
        $relation->setMetadata(null);
        $relation->setDefinitionOverride(null);
        $relation->setSubGroup(null);

        return new ScopedKeyAssignment($relation, self::GROUP_IDENTIFIER, '01JA');
    }
}
