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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryMapper;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedCollectionRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function array_key_exists;
use function array_keys;

/**
 * An artifact entry carries that entity's own columns and nothing a reader could not apply
 * somewhere else. The row ids and the timestamps are the ones that matter: an entry that carried
 * them would describe one installation rather than one configuration, and a reader could not tell
 * which of the two it was looking at.
 *
 * @internal
 */
final class ArtifactEntryMapperTest extends Unit
{
    private const string IDENTIFIER = '01JBQ8X9Z0ABCDEFGHJKMNPQRS';

    private const string GROUP_IDENTIFIER = '01JBQ8X9Z0TUVWXYZ23456789';

    private const string KEY_IDENTIFIER = '01JBQ8X9Z0KEYKEYKEYKEYKEY';

    private const string COLLECTION_IDENTIFIER = '01JBQ8X9Z0COLCOLCOLCOLCOL';

    private const int TIMESTAMP = 1734567890;

    private const string FIELD_LOCALIZED = 'localized';

    private InMemoryKeyLocalizationResolver $keyLocalizationResolver;

    public function _before(): void
    {
        $this->keyLocalizationResolver = new InMemoryKeyLocalizationResolver();
    }

    /**
     * The store's `engine` is not exported: flipping a store's storage engine moves stored values,
     * which is a migration of its own and not something a configuration file carries.
     */
    public function testTheStoreEntryCarriesTheIdentifierNameAndDescriptionOnly(): void
    {
        $store = new StoreConfig();
        $store->setId(1);
        $store->setIdentifier(self::IDENTIFIER);
        $store->setName('ETIM');
        $store->setDescription('The ETIM classification');
        $store->setEngine('column');

        $entry = $this->createEntryMapper()->mapStore($store);

        $this->assertSame(['identifier', 'name', 'description'], array_keys($entry));
        $this->assertSame(self::IDENTIFIER, $entry['identifier']);
        $this->assertSame('ETIM', $entry['name']);
        $this->assertSame('The ETIM classification', $entry['description']);
    }

    public function testTheSubGroupEntryCarriesTheIdentifierNameAndDescriptionOnly(): void
    {
        $config = new SubGroupConfig();
        $config->setId(21);
        $config->setStoreId(1);
        $config->setIdentifier(self::IDENTIFIER);
        $config->setName('Dimensions');
        $config->setDescription('Physical dimensions');
        $config->setCreationDate(self::TIMESTAMP);
        $config->setModificationDate(self::TIMESTAMP);

        $entry = $this->createEntryMapper()->mapSubGroup($config);

        $this->assertSame(['identifier', 'name', 'description', 'customMetadata'], array_keys($entry));
    }

    public function testTheCollectionEntryCarriesTheIdentifierNameAndDescriptionOnly(): void
    {
        $config = new CollectionConfig();
        $config->setId(31);
        $config->setStoreId(1);
        $config->setIdentifier(self::IDENTIFIER);
        $config->setName('Lighting');
        $config->setDescription('Lighting products');
        $config->setCreationDate(self::TIMESTAMP);
        $config->setModificationDate(self::TIMESTAMP);

        $entry = $this->createEntryMapper()->mapCollection($config);

        $this->assertSame(['identifier', 'name', 'description', 'customMetadata'], array_keys($entry));
    }

    /**
     * `parentId` is not exported either - a group tree is not part of what a store configuration
     * carries.
     */
    public function testTheGroupEntryCarriesNeitherParentIdNorTheRowsOwnColumns(): void
    {
        $config = new GroupConfig();
        $config->setId(41);
        $config->setStoreId(1);
        $config->setParentId(7);
        $config->setIdentifier(self::IDENTIFIER);
        $config->setName('Measurements');
        $config->setDescription('Measured properties');
        $config->setCreationDate(self::TIMESTAMP);
        $config->setModificationDate(self::TIMESTAMP);

        $entry = $this->createEntryMapper()->mapGroup($config);

        $this->assertSame(['identifier', 'name', 'description', 'customMetadata'], array_keys($entry));
        $this->assertArrayNotHasKey('parentId', $entry);
    }

    /**
     * The definition travels decoded rather than as the JSON text the column holds, the way the key
     * endpoints publish it - and it is what carries the title through a core that re-derives the
     * column from the blob.
     */
    public function testTheKeyEntryCarriesItsOwnColumnsWithTheDefinitionDecoded(): void
    {
        $entry = $this->createEntryMapper()->mapKey($this->createKey(enabled: true));

        $this->assertSame(
            ['identifier', 'name', 'title', 'description', 'type', 'definition', 'enabled', 'customMetadata'],
            array_keys($entry)
        );
        $this->assertSame(['fieldtype' => 'input', 'title' => 'Weight'], $entry['definition']);
        $this->assertTrue($entry['enabled']);
    }

    /**
     * All four entity levels write `customMetadata` verbatim, as an explicit null where the row holds
     * none - the member is never left out, which is what tells an apply the entity carries none.
     */
    public function testEveryEntityLevelWritesItsPairsAndANullWhereThereAreNone(): void
    {
        $mapper = $this->createEntryMapper();
        $pairs = ['indexed' => 'yes', 'nested' => ['a' => 1]];

        $subGroup = new SubGroupConfig();
        $subGroup->setName('Dimensions');
        $collection = new CollectionConfig();
        $collection->setName('Lighting');
        $group = new GroupConfig();
        $group->setName('Measurements');
        $key = $this->createKey(enabled: true);

        $this->assertNull($mapper->mapSubGroup($subGroup)['customMetadata']);
        $this->assertNull($mapper->mapCollection($collection)['customMetadata']);
        $this->assertNull($mapper->mapGroup($group)['customMetadata']);
        $this->assertNull($mapper->mapKey($key)['customMetadata']);

        $subGroup->setCustomMetadata($pairs);
        $collection->setCustomMetadata($pairs);
        $group->setCustomMetadata($pairs);
        $key->setCustomMetadata($pairs);

        $this->assertSame($pairs, $mapper->mapSubGroup($subGroup)['customMetadata']);
        $this->assertSame($pairs, $mapper->mapCollection($collection)['customMetadata']);
        $this->assertSame($pairs, $mapper->mapGroup($group)['customMetadata']);
        $this->assertSame($pairs, $mapper->mapKey($key)['customMetadata']);
    }

    /**
     * A disabled key travels with the rest, carrying the flag that says so - the artifact is the
     * whole store, not the part of it that happens to be in use.
     */
    public function testADisabledKeyIsExportedWithItsFlag(): void
    {
        $entry = $this->createEntryMapper()->mapKey($this->createKey(enabled: false));

        $this->assertFalse($entry['enabled']);
    }

    /**
     * The key's own `localized` flag travels only where the key states one. A key that states none
     * produces an entry with no such member at all, which is also the entry a file written before
     * the flag existed carries - so the two are one shape and apply has one answer for them.
     *
     * @dataProvider localizedProvider
     */
    public function testTheLocalizedFlagIsExportedOnlyWhereTheKeyStatesOne(?bool $localized, bool $present): void
    {
        $entry = $this->createEntryMapper()->mapKey($this->createKey(enabled: true, localized: $localized));

        $this->assertSame($present, array_key_exists(self::FIELD_LOCALIZED, $entry));

        if ($present) {
            $this->assertSame($localized, $entry[self::FIELD_LOCALIZED]);
        }
    }

    /**
     * @return array<string, array{0: ?bool, 1: bool}>
     */
    public static function localizedProvider(): array
    {
        return [
            'a key that states nothing carries no member' => [null, false],
            'a key flagged localized carries true' => [true, true],
            'a key flagged not localized carries false' => [false, true],
        ];
    }

    /**
     * The own columns of `classificationstore_relations` and the two endpoints, and nothing else:
     * `name`, `description`, `definition`, `type` and `enabled` are joined from the key, and
     * `groupName` from the group, so they belong to those entries.
     */
    public function testTheKeyAssignmentEntryCarriesTheRelationsOwnColumnsOnly(): void
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(51);
        $relation->setGroupId(41);
        $relation->setSorter(3);
        $relation->setMandatory(true);
        $relation->setMetadata(['source' => 'etim']);
        $relation->setDefinitionOverride(['defaultUnit' => 'kg']);
        $relation->setSubGroup(self::IDENTIFIER);

        // the joined columns a hydrated relation also carries
        $relation->setName('weight');
        $relation->setDescription('The weight');
        $relation->setDefinition('{"fieldtype":"input"}');
        $relation->setType('input');
        $relation->setEnabled(true);

        $entry = $this->createEntryMapper()->mapKeyAssignment(
            new ScopedKeyAssignment($relation, self::GROUP_IDENTIFIER, self::KEY_IDENTIFIER)
        );

        $this->assertSame(
            ['group', 'key', 'sorter', 'mandatory', 'metadata', 'definitionOverride', 'subGroup'],
            array_keys($entry)
        );
        $this->assertSame(self::GROUP_IDENTIFIER, $entry['group']);
        $this->assertSame(self::KEY_IDENTIFIER, $entry['key']);
        $this->assertSame(['source' => 'etim'], $entry['metadata']);
        $this->assertSame(['defaultUnit' => 'kg'], $entry['definitionOverride']);
        $this->assertSame(self::IDENTIFIER, $entry['subGroup']);
    }

    /**
     * `sorter` is the only own column of `classificationstore_collectionrelations`; `name` and
     * `description` are joined from the group.
     */
    public function testTheCollectionRelationEntryCarriesItsSorterOnly(): void
    {
        $relation = new CollectionGroupRelation();
        $relation->setColId(31);
        $relation->setGroupId(41);
        $relation->setSorter(5);
        $relation->setName('Measurements');
        $relation->setDescription('Measured properties');

        $entry = $this->createEntryMapper()->mapCollectionRelation(
            new ScopedCollectionRelation($relation, self::COLLECTION_IDENTIFIER, self::GROUP_IDENTIFIER)
        );

        $this->assertSame(['collection', 'group', 'sorter'], array_keys($entry));
        $this->assertSame(self::COLLECTION_IDENTIFIER, $entry['collection']);
        $this->assertSame(self::GROUP_IDENTIFIER, $entry['group']);
        $this->assertSame(5, $entry['sorter']);
    }

    private function createEntryMapper(): ArtifactEntryMapper
    {
        return new ArtifactEntryMapper($this->keyLocalizationResolver);
    }

    private function createKey(bool $enabled, ?bool $localized = null): KeyConfig
    {
        $config = new KeyConfig();
        $config->setId(51);
        $config->setStoreId(1);
        $config->setIdentifier(self::KEY_IDENTIFIER);
        $config->setName('weight');
        $config->setTitle('Weight');
        $config->setDescription('The weight');
        $config->setType('input');
        $config->setDefinition('{"fieldtype":"input","title":"Weight"}');
        $config->setEnabled($enabled);
        $config->setCreationDate(self::TIMESTAMP);
        $config->setModificationDate(self::TIMESTAMP);
        $this->keyLocalizationResolver->setLocalized($config, $localized);

        return $config;
    }
}
