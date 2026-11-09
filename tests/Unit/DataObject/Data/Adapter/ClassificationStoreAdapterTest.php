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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Data\Adapter;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\DefinitionCacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\GroupConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface as ElementServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\EngineCapabilityGuard;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializer;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\StoreEngineResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\ClassificationStoreAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\ManyToManyRelationAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField as EncryptedFieldDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue as QuantityValueDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\EncryptedField;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\UserInterface;
use function array_keys;
use function array_map;
use function array_merge;
use function json_encode;
use function sprintf;

/**
 * @internal
 */
final class ClassificationStoreAdapterTest extends Unit
{
    private const string PREVIEW_FIELD = 'previewField';

    private const array GROUP_NAMES = [10 => 'Colour', 20 => 'Size'];

    private const int STORE_ID = 3;

    private const int QUALIFIED_GROUP = 40;

    private const int QUALIFIED_KEY = 400;

    private const int PLAIN_KEY = 401;

    private const int OTHER_PLAIN_KEY = 402;

    private const int OTHER_QUALIFIED_KEY = 403;

    /**
     * An engine reporting both capabilities, which is what a write of a list needs to be judged
     * against at all - the capability gate is fail-closed, so a test about anything else has to
     * state that the engine is able to hold what it writes.
     */
    private const array BOTH_CAPABILITIES = [
        StoreEngineResolverInterface::FEATURE_MULTI_VALUE,
        StoreEngineResolverInterface::FEATURE_VARIANTS,
    ];

    private const string LANGUAGE = 'default';

    private const string GERMAN = 'de';

    private const string METRIC = 'metric';

    private const string IMPERIAL = 'imperial';

    private const int UNPROCESSABLE = 422;

    /**
     * @throws Exception
     */
    public function testGetDataForSetterReturnsNull(): void
    {
        $adapter = $this->createAdapter();
        $element = $this->makeEmpty(Concrete::class);
        $fieldDefinition = $this->makeEmpty(Input::class);
        $user = $this->makeEmpty(UserInterface::class);

        $result = $adapter->getDataForSetter(
            $element,
            $fieldDefinition,
            'testField',
            ['testField' => []],
            $user
        );

        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterWithAllGroupsDeleted(): void
    {
        $existingContainer = new Classificationstore();
        $existingContainer->setActiveGroups([11 => true]);
        $existingContainer->setGroupCollectionMappings([11 => 7]);

        $element = $this->makeEmpty(Concrete::class, [
            'get' => $existingContainer,
        ]);

        $fieldDefinition = $this->make(ClassificationstoreDefinition::class, [
            'isLocalized' => false,
        ]);

        $user = $this->makeEmpty(UserInterface::class);

        $adapter = $this->createAdapter();

        $data = [
            'myStore' => [
                11 => ['action' => 'deleted'],
                'activeGroups' => [11 => true],
                'groupCollectionMapping' => [11 => 7],
            ],
        ];

        $result = $adapter->getDataForSetter(
            $element,
            $fieldDefinition,
            'myStore',
            $data,
            $user
        );

        $this->assertInstanceOf(Classificationstore::class, $result);
        $this->assertEmpty($result->getActiveGroups());
        $this->assertEmpty($result->getGroupCollectionMappings());
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterWithPartialGroupDeletion(): void
    {
        $existingContainer = $this->make(Classificationstore::class, [
            'setLocalizedKeyValue' => function () use (&$existingContainer) {
                return $existingContainer;
            },
        ]);

        $element = $this->makeEmpty(Concrete::class, [
            'get' => $existingContainer,
        ]);

        $keyConfig = new KeyConfig();

        $fieldDefinition = $this->make(ClassificationstoreDefinition::class, [
            'isLocalized' => false,
            'getKeyConfiguration' => $keyConfig,
        ]);

        $user = $this->makeEmpty(UserInterface::class, ['isAdmin' => true]);

        $inputAdapter = $this->makeEmpty(SetterDataInterface::class, [
            'getDataForSetter' => 'new-value',
        ]);

        $inputDefinition = $this->makeEmpty(Data::class, [
            'getName' => 'inputField',
        ]);

        $adapter = $this->createAdapter(
            dataAdapterService: $this->makeEmpty(DataAdapterServiceInterface::class, [
                'tryDataAdapter' => $inputAdapter,
            ]),
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getFieldDefinitionFromKeyConfig' => $inputDefinition,
            ]),
        );

        $data = [
            'myStore' => [
                11 => ['action' => 'deleted'],
                22 => [
                    'default' => [
                        2 => 'new-value',
                    ],
                ],
                'activeGroups' => [11 => true, 22 => true],
                'groupCollectionMapping' => [11 => 7, 22 => 8],
            ],
        ];

        $result = $adapter->getDataForSetter(
            $element,
            $fieldDefinition,
            'myStore',
            $data,
            $user
        );

        $this->assertInstanceOf(Classificationstore::class, $result);

        $activeGroups = $result->getActiveGroups();
        $this->assertArrayHasKey(22, $activeGroups);
        $this->assertArrayNotHasKey(11, $activeGroups);

        $mappings = $result->getGroupCollectionMappings();
        $this->assertArrayHasKey(22, $mappings);
        $this->assertArrayNotHasKey(11, $mappings);
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterPreservesActiveGroupWithNoKeyData(): void
    {
        $element = $this->makeEmpty(Concrete::class, [
            'get' => new Classificationstore(),
        ]);

        $fieldDefinition = $this->make(ClassificationstoreDefinition::class, [
            'isLocalized' => false,
        ]);

        $user = $this->makeEmpty(UserInterface::class);

        $adapter = $this->createAdapter();

        // Group 5 is active and has a collection mapping, but NO key data in the store
        $data = [
            'myStore' => [
                'activeGroups' => [5 => true],
                'groupCollectionMapping' => [5 => 3],
            ],
        ];

        $result = $adapter->getDataForSetter(
            $element,
            $fieldDefinition,
            'myStore',
            $data,
            $user
        );

        $this->assertInstanceOf(Classificationstore::class, $result);

        $activeGroups = $result->getActiveGroups();
        $this->assertArrayHasKey(5, $activeGroups);
        $this->assertTrue($activeGroups[5]);

        $mappings = $result->getGroupCollectionMappings();
        $this->assertArrayHasKey(5, $mappings);
        $this->assertEquals(3, $mappings[5]);
    }

    /**
     * @throws Exception
     */
    public function testEmptyActiveGroups(): void
    {
        $existingContainer = new Classificationstore();

        $element = $this->makeEmpty(Concrete::class, [
            'get' => $existingContainer,
        ]);

        $fieldDefinition = $this->make(ClassificationstoreDefinition::class, [
            'isLocalized' => false,
        ]);

        $user = $this->makeEmpty(UserInterface::class);

        $adapter = $this->createAdapter();

        $data = [
            'myStore' => [
                'activeGroups' => [],
                'groupCollectionMapping' => [],
            ],
        ];

        $result = $adapter->getDataForSetter(
            $element,
            $fieldDefinition,
            'myStore',
            $data,
            $user
        );

        $this->assertInstanceOf(Classificationstore::class, $result);
        $this->assertEmpty($result->getActiveGroups());
        $this->assertEmpty($result->getGroupCollectionMappings());
    }

    /**
     * @throws Exception
     */
    public function testClearStaleCollectionMappingsOnDeletion(): void
    {
        $existingContainer = new Classificationstore();
        $existingContainer->setActiveGroups([11 => true]);
        $existingContainer->setGroupCollectionMappings([11 => 7]);

        $element = $this->makeEmpty(Concrete::class, [
            'get' => $existingContainer,
        ]);

        $fieldDefinition = $this->make(ClassificationstoreDefinition::class, [
            'isLocalized' => false,
        ]);

        $user = $this->makeEmpty(UserInterface::class);

        $adapter = $this->createAdapter();

        $data = [
            'myStore' => [
                11 => ['action' => 'deleted'],
                'activeGroups' => [11 => true],
                'groupCollectionMapping' => [11 => 7],
            ],
        ];

        $result = $adapter->getDataForSetter(
            $element,
            $fieldDefinition,
            'myStore',
            $data,
            $user
        );

        $this->assertEmpty($result->getGroupCollectionMappings());
    }

    /**
     * Tests that multiple groups with mixed states (some deleted, some with data,
     * some active but empty) are all handled correctly.
     *
     * @throws Exception
     *
     */
    public function testMixedGroupStates(): void
    {
        $existingContainer = $this->make(Classificationstore::class, [
            'setLocalizedKeyValue' => function () use (&$existingContainer) {
                return $existingContainer;
            },
        ]);

        $element = $this->makeEmpty(Concrete::class, [
            'get' => $existingContainer,
        ]);

        $keyConfig = new KeyConfig();

        $fieldDefinition = $this->make(ClassificationstoreDefinition::class, [
            'isLocalized' => false,
            'getKeyConfiguration' => $keyConfig,
        ]);

        $user = $this->makeEmpty(UserInterface::class, ['isAdmin' => true]);

        $inputAdapter = $this->makeEmpty(SetterDataInterface::class, [
            'getDataForSetter' => 'updated-value',
        ]);

        $inputDefinition = $this->makeEmpty(Data::class, [
            'getName' => 'inputField',
        ]);

        $adapter = $this->createAdapter(
            dataAdapterService: $this->makeEmpty(DataAdapterServiceInterface::class, [
                'tryDataAdapter' => $inputAdapter,
            ]),
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getFieldDefinitionFromKeyConfig' => $inputDefinition,
            ]),
        );

        $data = [
            'myStore' => [
                // Group 10: deleted
                10 => ['action' => 'deleted'],
                // Group 20: has key data
                20 => [
                    'default' => [
                        200 => 'updated-value',
                    ],
                ],
                // Group 30: active but no key data submitted (partial fill)
                'activeGroups' => [10 => true, 20 => true, 30 => true],
                'groupCollectionMapping' => [10 => 1, 20 => 2, 30 => 3],
            ],
        ];

        $result = $adapter->getDataForSetter(
            $element,
            $fieldDefinition,
            'myStore',
            $data,
            $user
        );

        $this->assertInstanceOf(Classificationstore::class, $result);

        $activeGroups = $result->getActiveGroups();
        // Group 10 should be deleted
        $this->assertArrayNotHasKey(10, $activeGroups);
        // Group 20 should be active (has data)
        $this->assertArrayHasKey(20, $activeGroups);
        // Group 30 should be active (preserved from original activeGroups)
        $this->assertArrayHasKey(30, $activeGroups);

        $mappings = $result->getGroupCollectionMappings();
        // Only groups 20 and 30 should have mappings
        $this->assertArrayNotHasKey(10, $mappings);
        $this->assertArrayHasKey(20, $mappings);
        $this->assertArrayHasKey(30, $mappings);
    }

    /**
     * A non admin user whose language permissions grant the language independent column must be
     * able to store values in it, exactly like in the classic UI.
     *
     * @throws Exception
     */
    public function testGetDataForSetterStoresLanguageIndependentValuesForAllowedNonAdmin(): void
    {
        $storedLanguages = $this->getStoredLanguagesForNonAdmin(['default', 'de', 'en']);

        $this->assertContains('default', $storedLanguages);
    }

    /**
     * A non admin user whose language permissions exclude the language independent column must not
     * be able to store values in it.
     *
     * @throws Exception
     */
    public function testGetDataForSetterSkipsLanguageIndependentValuesForDeniedNonAdmin(): void
    {
        $storedLanguages = $this->getStoredLanguagesForNonAdmin(['de', 'en']);

        $this->assertNotContains('default', $storedLanguages);
        $this->assertContains('de', $storedLanguages);
    }

    /**
     * A non admin user whose language permissions grant only the language independent column must
     * not be able to store values in concrete localized columns.
     *
     * @throws Exception
     */
    public function testGetDataForSetterSkipsConcreteLanguagesWhenOnlyDefaultIsGranted(): void
    {
        $storedLanguages = $this->getStoredLanguagesForNonAdmin(['default']);

        $this->assertContains('default', $storedLanguages);
        $this->assertNotContains('de', $storedLanguages);
    }

    /**
     * The batched read asks the resolver once for all active groups, so it has to put every relation
     * back under the group it was loaded for, in the order the resolver handed it over. The key ids
     * are non contiguous and not ascending, so a lookup by position or a sort by key id would show up.
     *
     * @throws Exception
     */
    public function testGetDetailDataGroupsKeysByGroupInResolverOrder(): void
    {
        $requestedGroupIds = [];
        $adapter = $this->createAdapterReadingRelations($this->relationsOfTwoGroups(), $requestedGroupIds);

        $result = $adapter->getDetailData(
            $this->makeEmpty(Concrete::class),
            $this->createStore([10 => true, 20 => true, 30 => false]),
            $this->make(ClassificationstoreDefinition::class, ['isLocalized' => false])
        );

        // group 30 is inactive, so it is neither asked for nor part of the result
        $this->assertSame([10, 20], $requestedGroupIds);
        $this->assertArrayNotHasKey(30, $result);

        $this->assertSame([51, 7], array_keys($result[10]['default']));
        $this->assertSame('g10-k51-default', $result[10]['default'][51]);
        $this->assertSame('g10-k7-default', $result[10]['default'][7]);
        $this->assertSame([33 => 'g20-k33-default'], $result[20]['default']);
    }

    /**
     * The preview path reads the same batch and labels each value with the group it belongs to, so
     * a relation attributed to the wrong group would surface as the wrong label.
     *
     * @throws Exception
     */
    public function testGetPreviewFieldDataLabelsEveryKeyOfEveryActiveGroup(): void
    {
        $requestedGroupIds = [];
        $adapter = $this->createAdapterReadingRelations($this->relationsOfTwoGroups(), $requestedGroupIds);

        $data = $adapter->getPreviewFieldData(
            $this->createStore([10 => true, 20 => true, 30 => false]),
            $this->make(ClassificationstoreDefinition::class, ['isLocalized' => false]),
            ['otherField' => 'untouched']
        );

        $this->assertSame([10, 20], $requestedGroupIds);
        $this->assertSame('untouched', $data['otherField']);

        $this->assertSame(
            ['Colour - key-51', 'Colour - key-7', 'Size - key-33'],
            array_keys($data[self::PREVIEW_FIELD])
        );
        $this->assertSame('g10-k51-default', $data[self::PREVIEW_FIELD]['Colour - key-51']);
        $this->assertSame('g10-k7-default', $data[self::PREVIEW_FIELD]['Colour - key-7']);
        $this->assertSame('g20-k33-default', $data[self::PREVIEW_FIELD]['Size - key-33']);
    }

    /**
     * A key declaring neither `variants` nor `multiValue` serialises exactly as it did before either
     * member existed: a single normalized value at `<groupId>.<language>.<keyId>`. The whole array is
     * asserted as a literal rather than re-derived, so a list creeping in anywhere shows up.
     *
     * @dataProvider unqualifiedDeclarationProvider
     *
     * @param array<string, mixed> $declaration
     *
     * @throws Exception
     */
    public function testAStoreOfUnqualifiedKeysSerialisesAsASingleValuePerSlot(array $declaration): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::PLAIN_KEY => '80',
            self::OTHER_PLAIN_KEY => '0',
        ]]];

        $keyConfigs = $this->unqualifiedKeyConfigs($declaration);
        $adapter = $this->createQualifiedAdapter($keyConfigs, $stored);

        $this->assertSame(
            [
                'activeGroups' => [self::QUALIFIED_GROUP => true],
                'groupCollectionMapping' => [],
                self::QUALIFIED_GROUP => [
                    self::LANGUAGE => [
                        self::PLAIN_KEY => '80',
                        self::OTHER_PLAIN_KEY => '0',
                    ],
                ],
            ],
            $adapter->getDetailData(
                $this->makeEmpty(Concrete::class),
                $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]),
                $this->createQualifiedFieldDefinition($keyConfigs)
            )
        );
    }

    /**
     * The write path of such a key is unchanged in effect: one call to setLocalizedKeyValue per
     * slot, carrying the value the key's own type adapter handed back.
     *
     * @dataProvider unqualifiedDeclarationProvider
     *
     * @param array<string, mixed> $declaration
     *
     * @throws Exception
     */
    public function testAnUnqualifiedKeyIsWrittenWithTheSameArgumentsAsBefore(array $declaration): void
    {
        $this->assertSame(
            [[self::QUALIFIED_GROUP, self::PLAIN_KEY, '80', self::LANGUAGE]],
            $this->writeAndRecord(
                $this->unqualifiedKeyConfigs($declaration),
                [self::PLAIN_KEY => '80']
            )
        );
    }

    /**
     * The value of an unqualified key whose own type normalizes to a list of objects carrying `v`
     * travels whole in both directions. A table whose one column is named `v` is stored exactly
     * like this by {@see \Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\TableAdapter},
     * and while the shape of the value decided how a slot was read, this row was read as two
     * entries and published as its first cell - for a key that declares no qualifier at all.
     *
     * Asserted over the read and the write of the one fixture, because the two have to agree: a
     * read that published `'80'` and a write that stored the table would lose the table on the
     * first save of what was read.
     *
     * @dataProvider unqualifiedDeclarationProvider
     *
     * @param array<string, mixed> $declaration
     *
     * @throws Exception
     */
    public function testAnUnqualifiedKeyHoldingAListOfEntryShapedRowsTravelsWhole(array $declaration): void
    {
        $table = [['v' => '80'], ['v' => '81']];
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [self::PLAIN_KEY => $table]]];
        $keyConfigs = $this->unqualifiedKeyConfigs($declaration);

        $detail = $this->createQualifiedAdapter($keyConfigs, $stored)->getDetailData(
            $this->makeEmpty(Concrete::class),
            $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]),
            $this->createQualifiedFieldDefinition($keyConfigs)
        );

        $this->assertSame($table, $detail[self::QUALIFIED_GROUP][self::LANGUAGE][self::PLAIN_KEY]);

        $this->assertSame(
            [[self::QUALIFIED_GROUP, self::PLAIN_KEY, $table, self::LANGUAGE]],
            $this->writeAndRecord($keyConfigs, [self::PLAIN_KEY => $table], $stored)
        );
    }

    /**
     * The other round trip of the same ambiguity, on the declaring arm: one entry of a declaring
     * key whose value is itself a list of objects carrying `v`. It is stored as the one-entry list
     * the declaration puts it in - never collapsed to the bare value, which is what the table alone
     * would be - and read back as the one entry holding the whole table.
     *
     * @throws Exception
     */
    public function testAStoredEntryHoldingAnEntryShapedValueSurvivesTheRoundTrip(): void
    {
        $table = [['v' => 'cell'], ['v' => 'other']];
        $stored = [];

        $detail = $this->roundTrip($stored, [self::QUALIFIED_KEY => [['v' => $table]]]);

        $this->assertSame(
            [['v' => $table]],
            $stored[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]
        );
        $this->assertSame(
            [['v' => $table]],
            $detail[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]
        );
    }

    /**
     * Every declaration that permits exactly the one slot a key always had, so that the two
     * assertions above are made of each of them and not only of a blob carrying neither member.
     *
     * A key carrying `variants: []` or `multiValue: false` declares a member and still permits only
     * the default slot; so would every key of every store that already exists, the day a core
     * reports both members for all of them. Reading the presence of a member rather than what it
     * says would change the payload shape of all of those keys at once.
     *
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function unqualifiedDeclarationProvider(): array
    {
        return [
            'neither member' => [['fieldtype' => 'input']],
            'no variant beside the default one' => [['variants' => []]],
            'one value per variant' => [['multiValue' => false]],
            'both, stating the single slot' => [['variants' => [], 'multiValue' => false]],
        ];
    }

    /**
     * A key declaring either member carries its values at the same slot as an ordered list of
     * entries, each holding the normalized value plus only the qualifiers it has: an entry with no
     * qualifiers is emitted without `var` and without `p`.
     *
     * @throws Exception
     */
    public function testAQualifiedKeySerialisesItsValuesAsAnOrderedListOfEntries(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => self::storedSlot([
                ['v' => '80'],
                ['v' => '176', 'var' => self::IMPERIAL],
                ['v' => '81', 'p' => 1],
            ]),
        ]]];

        $adapter = $this->createQualifiedAdapter($this->qualifiedKeyConfigs(), $stored);

        $detail = $adapter->getDetailData(
            $this->makeEmpty(Concrete::class),
            $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]),
            $this->createQualifiedFieldDefinition($this->qualifiedKeyConfigs())
        );

        $this->assertSame(
            [
                ['v' => '80'],
                ['v' => '176', 'var' => self::IMPERIAL],
                ['v' => '81', 'p' => 1],
            ],
            $detail[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]
        );
    }

    /**
     * A metric and an imperial value set on one key through the write path are read back separately
     * from the read path, over the one container they were written to.
     *
     * @throws Exception
     */
    public function testTwoVariantsWrittenToOneKeyAreReadBackSeparately(): void
    {
        $stored = [];
        $detail = $this->roundTrip(
            $stored,
            [self::QUALIFIED_KEY => [
                ['v' => '80', 'var' => self::METRIC],
                ['v' => '176', 'var' => self::IMPERIAL],
            ]]
        );

        $this->assertSame(
            [
                ['v' => '80', 'var' => self::METRIC],
                ['v' => '176', 'var' => self::IMPERIAL],
            ],
            $detail[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]
        );
    }

    /**
     * A repeatable key's list is read back in the order it was written, which the positions and not
     * the order of the stored array carry.
     *
     * @throws Exception
     */
    public function testARepeatableKeysListIsReadBackInTheOrderItWasWritten(): void
    {
        $stored = [];
        $detail = $this->roundTrip(
            $stored,
            [self::QUALIFIED_KEY => [
                ['v' => 'first'],
                ['v' => 'second', 'p' => 1],
                ['v' => 'third', 'p' => 2],
            ]]
        );

        $this->assertSame(
            [
                ['v' => 'first'],
                ['v' => 'second', 'p' => 1],
                ['v' => 'third', 'p' => 2],
            ],
            $detail[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]
        );
    }

    /**
     * A patch carrying one entry changes the value in that slot only and leaves the other entries of
     * the key's list intact, matched by qualifier and never by position in the array.
     *
     * @throws Exception
     */
    public function testAPatchOfOneEntryLeavesTheOtherEntriesOfTheListIntact(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => self::storedSlot([
                ['v' => 'first'],
                ['v' => 'second', 'p' => 1],
                ['v' => 'third', 'p' => 2],
            ]),
        ]]];

        $written = $this->writeAndRecord(
            $this->qualifiedKeyConfigs(),
            [self::QUALIFIED_KEY => [['v' => 'patched', 'p' => 1]]],
            $stored,
            isPatch: true,
            capabilities: self::BOTH_CAPABILITIES
        );

        $this->assertSame(
            [[self::QUALIFIED_GROUP, self::QUALIFIED_KEY, self::storedSlot([
                ['v' => 'first'],
                ['v' => 'patched', 'p' => 1],
                ['v' => 'third', 'p' => 2],
            ]), self::LANGUAGE]],
            $written
        );
    }

    /**
     * The merge base is the row, not the view of it the patch merges into.
     *
     * `$elementStore` is built under the user's language *view* permissions, so a language this
     * user may edit and may not view is missing from it entirely. Merging the write's one entry
     * against that absence would drop the two entries it leaves alone, which is why the merge reads
     * the container instead.
     *
     * @throws Exception
     */
    public function testAPatchKeepsTheEntriesOfALanguageTheNormalizedViewCannotSee(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::GERMAN => [
            self::QUALIFIED_KEY => self::storedSlot([
                ['v' => 'first'],
                ['v' => 'second', 'p' => 1],
                ['v' => 'third', 'p' => 2],
            ]),
        ]]];

        $written = [];
        $keyConfigs = $this->qualifiedKeyConfigs();
        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true], $written);

        $adapter = $this->createQualifiedAdapter(
            $keyConfigs,
            $stored,
            self::BOTH_CAPABILITIES,
            languageService: $this->createLanguageService(viewable: [], editable: [self::GERMAN])
        );

        $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class, ['get' => $container]),
            $this->createQualifiedFieldDefinition($keyConfigs, isLocalized: true),
            'myStore',
            ['myStore' => [
                self::QUALIFIED_GROUP => [
                    self::GERMAN => [self::QUALIFIED_KEY => [['v' => 'patched', 'p' => 1]]],
                ],
                'activeGroups' => [self::QUALIFIED_GROUP => true],
            ]],
            $this->makeEmpty(UserInterface::class, ['isAdmin' => false]),
            isPatch: true
        );

        $this->assertSame(
            [[self::QUALIFIED_GROUP, self::QUALIFIED_KEY, self::storedSlot([
                ['v' => 'first'],
                ['v' => 'patched', 'p' => 1],
                ['v' => 'third', 'p' => 2],
            ]), self::GERMAN]],
            $written
        );
    }

    /**
     * A patch takes only the entries the request supplied through the key's own type adapter, and
     * what it carries over from the row it carries over raw.
     *
     * The real {@see ManyToManyRelationAdapter} is driven here, because the two shapes that have to
     * disagree are its own rather than this suite's idea of them: `normalize()` answers with
     * {@see RelatedElementData} objects and its setter reads `$relation['type']` off each element
     * it is handed. Taking an untouched sibling through the setter a second time therefore hands
     * that setter its own normalized output and takes the whole patch down with an `Error` that
     * names no key - so a patch of one entry of a relation key could not be saved at all while a
     * sibling existed. The current value handed over is raw for the same reason: that is what every
     * other context reads this slot as, and what
     * {@see \Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataService::getSetterData()}
     * normalizes itself.
     *
     * The normalizing data service is stubbed because that is what made the defect reachable: the
     * write path does not ask for a normalized value at all any more, so the stub is answered zero
     * times now and was answered for every carried-over entry before.
     *
     * @throws Exception
     */
    public function testAPatchOfOneRelationEntryLeavesAnUntouchedSiblingRawInTheRow(): void
    {
        $sibling = [['type' => 'object', 'id' => 8]];
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => self::storedSlot([
                ['v' => [['type' => 'object', 'id' => 7]]],
                ['v' => $sibling, 'var' => self::IMPERIAL],
            ]),
        ]]];

        $relationAdapter = $this->createRelationAdapter();
        $keyConfigs = $this->qualifiedKeyConfigs();
        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]);

        $adapter = $this->createQualifiedAdapter(
            $keyConfigs,
            $stored,
            self::BOTH_CAPABILITIES,
            dataAdapterService: $this->makeEmpty(DataAdapterServiceInterface::class, [
                'tryDataAdapter' => $relationAdapter,
            ]),
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getFieldDefinitionFromKeyConfig' => $this->makeEmpty(Data::class, ['getName' => 'related']),
            ]),
            // what the real DataService does: the field's own adapter normalizes the value
            dataService: $this->makeEmpty(DataServiceInterface::class, [
                'getNormalizedValue' => static fn (
                    mixed $value,
                    Data $fieldDefinition
                ): mixed => $relationAdapter->normalize($value, $fieldDefinition),
                'getPreviewFieldName' => self::PREVIEW_FIELD,
            ])
        );

        $this->writeKeys(
            $adapter,
            $container,
            $this->createQualifiedFieldDefinition($keyConfigs),
            [self::QUALIFIED_KEY => [['v' => [['type' => 'object', 'id' => 9]]]]],
            isPatch: true
        );

        $slot = $stored[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY];

        // the untouched sibling is byte for byte the entry the row held
        $this->assertSame(['v' => $sibling, 'var' => self::IMPERIAL], $slot[1]);

        // and the one entry the patch named went through the relation setter
        $this->assertSame([9], array_map(
            static fn (Concrete $element): int => $element->getId(),
            $slot[0]['v']
        ));
    }

    /**
     * The real relation adapter, with the two collaborators it resolves elements through stubbed:
     * the shapes this is driven for are the adapter's own - `RelatedElementData` out of
     * `normalize()` and `$relation['type']` into the setter - and neither of them is reached
     * through the database the unit suite does not have.
     *
     * @throws Exception
     */
    private function createRelationAdapter(): ManyToManyRelationAdapter
    {
        $elements = [];
        foreach ([7, 8, 9] as $id) {
            $elements[$id] = $this->makeEmpty(Concrete::class, ['getId' => $id]);
        }

        return new ManyToManyRelationAdapter(
            $this->makeEmpty(RelationDataServiceInterface::class, [
                'getSetterData' => static fn (
                    Concrete $element,
                    Data $fieldDefinition,
                    DataNormalizerInterface $adapter,
                    bool $isPatch,
                    ?array $fieldData
                ): ?array => $fieldData,
                'getRelationElementsData' => static fn (array $relations): array => array_map(
                    static fn (array $relation): RelatedElementData => new RelatedElementData(
                        $relation['id'],
                        $relation['type'],
                        'Product',
                        '/products/' . $relation['id']
                    ),
                    $relations
                ),
            ]),
            $this->makeEmpty(ElementServiceResolverInterface::class, [
                'getElementById' => static fn (
                    string $type,
                    int $id
                ): ?Concrete => $elements[$id] ?? null,
            ])
        );
    }

    /**
     * The current value the key's own type adapter is handed is the one entry's, not the whole
     * list: the list is the state of the key, and an adapter merging into the current value - a
     * multi select or a relation in patch mode - would otherwise read the siblings of the value it
     * is about.
     *
     * @throws Exception
     */
    public function testTheKeysTypeAdapterSeesTheCurrentValueOfTheOneEntryItConverts(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => self::storedSlot([
                ['v' => 'first'],
                ['v' => 'second', 'p' => 1],
                ['v' => 'third', 'p' => 2],
            ]),
        ]]];

        $seen = $this->recordCurrentValuesSeenBySubAdapter(
            $stored,
            [self::QUALIFIED_KEY => [
                ['v' => 'A'],
                ['v' => 'B', 'p' => 1],
                ['v' => 'C', 'p' => 2],
                ['v' => 'D', 'var' => self::IMPERIAL],
            ]]
        );

        // one current value per entry, matched by qualifier, and null where the row holds no entry
        $this->assertSame(['first', 'second', 'third', null], $seen);
    }

    /**
     * On a store whose engine reports neither capability, a write that resolves to the single
     * default slot - one entry, no variant, position 0 - succeeds, and stores it as the one-entry
     * list the key's declaration puts it in rather than as the bare value.
     *
     * The shape of a declaring key's slot does not depend on how many entries are in it, which is
     * what {@see self::testAStoredEntryHoldingAnEntryShapedValueSurvivesTheRoundTrip()} needs: the
     * bare value this used to collapse to was indistinguishable from the value of a key whose own
     * type normalizes to a list of entry-shaped objects.
     *
     * @throws Exception
     */
    public function testTheSingleDefaultSlotIsWrittenOnAnEngineReportingNoCapability(): void
    {
        $this->assertSame(
            [[self::QUALIFIED_GROUP, self::QUALIFIED_KEY, [['v' => '80']], self::LANGUAGE]],
            $this->writeAndRecord(
                $this->qualifiedKeyConfigs(),
                [self::QUALIFIED_KEY => [['v' => '80']]],
                capabilities: []
            )
        );

        // and the same for a client that sends the value itself rather than a list: what it sends
        // decides which entry is written, never which shape the slot is stored in
        $this->assertSame(
            [[self::QUALIFIED_GROUP, self::QUALIFIED_KEY, [['v' => '80']], self::LANGUAGE]],
            $this->writeAndRecord(
                $this->qualifiedKeyConfigs(),
                [self::QUALIFIED_KEY => '80'],
                capabilities: []
            )
        );
    }

    /**
     * Only a write that actually needs the missing capability is refused, and it is refused before
     * anything is written.
     *
     * @throws Exception
     */
    public function testATwoEntryListIsRefusedOnAnEngineReportingNoCapability(): void
    {
        $written = [];

        try {
            $this->writeAndRecord(
                $this->qualifiedKeyConfigs(),
                [self::QUALIFIED_KEY => [['v' => '80'], ['v' => '81', 'p' => 1]]],
                capabilities: [],
                written: $written
            );
            $this->fail('Expected the write to be refused.');
        } catch (AbstractApiException $exception) {
            $this->assertSame(
                HttpResponseErrorKeys::ENGINE_CAPABILITY_MISSING->value,
                $exception->getErrorKey()
            );
            $this->assertSame(self::UNPROCESSABLE, $exception->getStatusCode());
        }

        $this->assertSame([], $written);
    }

    /**
     * Declaring a variant never makes the key uneditable: the same declaration that refuses a
     * two-entry list on this engine still accepts the default slot.
     *
     * @throws Exception
     */
    public function testDeclaringAVariantDoesNotMakeTheKeyUneditable(): void
    {
        $written = $this->writeAndRecord(
            $this->qualifiedKeyConfigs(['variants' => [self::METRIC, self::IMPERIAL]]),
            [self::QUALIFIED_KEY => [['v' => '80']]],
            capabilities: []
        );

        $this->assertSame(
            [[self::QUALIFIED_GROUP, self::QUALIFIED_KEY, [['v' => '80']], self::LANGUAGE]],
            $written
        );
    }

    /**
     * The write path of a key that declares qualifiers is where a payload naming an undeclared
     * variant is refused, rather than it reaching the storage.
     *
     * @throws Exception
     */
    public function testAnUndeclaredVariantIsRefusedOnTheWritePath(): void
    {
        $written = [];

        try {
            $this->writeAndRecord(
                $this->qualifiedKeyConfigs(),
                [self::QUALIFIED_KEY => [['v' => '80', 'var' => 'nautical']]],
                written: $written
            );
            $this->fail('Expected the write to be refused.');
        } catch (AbstractApiException $exception) {
            $this->assertSame(HttpResponseErrorKeys::UNDECLARED_VARIANT->value, $exception->getErrorKey());
        }

        $this->assertSame([], $written);
    }

    /**
     * A second position on a key that does not declare itself repeatable is refused on the write
     * path, by its own error key, however able the store's engine is.
     *
     * @throws Exception
     */
    public function testASecondPositionOnAKeyThatIsNotRepeatableIsRefusedOnTheWritePath(): void
    {
        $written = [];

        try {
            $this->writeAndRecord(
                $this->qualifiedKeyConfigs(['variants' => [self::METRIC, self::IMPERIAL]]),
                [self::QUALIFIED_KEY => [['v' => '80'], ['v' => '81', 'p' => 1]]],
                capabilities: self::BOTH_CAPABILITIES,
                written: $written
            );
            $this->fail('Expected the write to be refused.');
        } catch (AbstractApiException $exception) {
            $this->assertSame(HttpResponseErrorKeys::KEY_NOT_REPEATABLE->value, $exception->getErrorKey());
        }

        $this->assertSame([], $written);
    }

    /**
     * The capability gate is fail-closed on the one answer the production resolver gives against a
     * core that ships no engine resolver at all - null, from
     * {@see StoreEngineResolverInterface::getCapabilitiesOfStore()}. That is the very core the gate
     * exists to protect, so a write needing a capability is refused there rather than going through
     * unchecked, and nothing is written.
     *
     * @throws Exception
     */
    public function testAWriteNeedingACapabilityIsRefusedWhereTheCoreCannotBeAsked(): void
    {
        $written = [];

        try {
            $this->writeAndRecord(
                $this->qualifiedKeyConfigs(),
                [self::QUALIFIED_KEY => [
                    ['v' => '80', 'var' => self::METRIC],
                    ['v' => '176', 'var' => self::IMPERIAL],
                ]],
                capabilities: null,
                written: $written
            );
            $this->fail('Expected the write to be refused.');
        } catch (AbstractApiException $exception) {
            $this->assertSame(
                HttpResponseErrorKeys::ENGINE_CAPABILITY_MISSING->value,
                $exception->getErrorKey()
            );
        }

        $this->assertSame([], $written);

        // and the sibling case stays open: the single default slot needs no capability to be written
        $this->assertSame(
            [[self::QUALIFIED_GROUP, self::QUALIFIED_KEY, [['v' => '80']], self::LANGUAGE]],
            $this->writeAndRecord(
                $this->qualifiedKeyConfigs(),
                [self::QUALIFIED_KEY => [['v' => '80']]],
                capabilities: null
            )
        );
    }

    /**
     * A value of an encrypted key that did not come back encrypted refuses the write.
     *
     * {@see \Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait::validateEncryptedField()}
     * reads one value of the key's own type, so it is asked per entry. Handed the wrapped list it
     * would see no encrypted field at all and skip the whole slot while the response says success -
     * a secret lost, reported as saved.
     *
     * @throws Exception
     */
    public function testAnEncryptedEntryThatDidNotComeBackEncryptedRefusesTheWrite(): void
    {
        $written = [];

        try {
            $this->writeEncryptedEntries(wrapped: false, written: $written);
            $this->fail('Expected the write to be refused.');
        } catch (AbstractApiException $exception) {
            $this->assertSame(HttpResponseErrorKeys::INVALID_ARGUMENT->value, $exception->getErrorKey());
            $this->assertSame(self::UNPROCESSABLE, $exception->getStatusCode());
        }

        $this->assertSame([], $written);
    }

    /**
     * The sibling case: a value that did come back encrypted is stored, as the entry list carrying
     * it. So the refusal above is the guard firing on a value that went missing, and not encrypted
     * keys being closed to qualified writes.
     *
     * @throws Exception
     */
    public function testAnEncryptedEntryThatCameBackEncryptedIsStored(): void
    {
        $written = $this->writeEncryptedEntries(wrapped: true);

        $this->assertCount(1, $written);
        $this->assertSame(
            [self::QUALIFIED_GROUP, self::QUALIFIED_KEY, self::LANGUAGE],
            [$written[0][0], $written[0][1], $written[0][3]]
        );

        $entries = $written[0][2];

        $this->assertSame([['v'], ['v', 'var']], [array_keys($entries[0]), array_keys($entries[1])]);
        $this->assertSame(
            ['80', '176'],
            [$entries[0]['v']->getPlain(), $entries[1]['v']->getPlain()]
        );
    }

    /**
     * A patch is held to what the request actually named.
     *
     * {@see ClassificationStoreAdapter} merges the stored store into the write, so a slot the
     * request never supplied arrives inside this bundle's own patch. It must not then be judged
     * against the key's declaration as that declaration reads now, or a patch touching one key
     * would fail because another key of the same bucket holds a variant an administrator has since
     * removed from it.
     *
     * @throws Exception
     */
    public function testAPatchIsNotRefusedByAnEntryItNeverSupplied(): void
    {
        $keyConfigs = [
            self::QUALIFIED_KEY => $this->createKeyConfig([
                'variants' => [self::METRIC, self::IMPERIAL],
                'multiValue' => true,
            ]),
            // the declaration has narrowed: the stored imperial entry below is no longer permitted
            self::OTHER_QUALIFIED_KEY => $this->createKeyConfig(['variants' => [self::METRIC]]),
        ];

        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => self::storedSlot([['v' => '80'], ['v' => '81', 'p' => 1]]),
            self::OTHER_QUALIFIED_KEY => self::storedSlot([['v' => '176', 'var' => self::IMPERIAL]]),
        ]]];

        $written = [];
        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true], $written);
        $adapter = $this->createQualifiedAdapter($keyConfigs, $stored, self::BOTH_CAPABILITIES);

        $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class, ['get' => $container]),
            $this->createQualifiedFieldDefinition($keyConfigs),
            'myStore',
            ['myStore' => [
                self::QUALIFIED_GROUP => [
                    self::LANGUAGE => [self::QUALIFIED_KEY => [['v' => 'patched', 'p' => 1]]],
                ],
                'activeGroups' => [self::QUALIFIED_GROUP => true],
            ]],
            $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
            isPatch: true
        );

        // the named key alone is written, and the slot the request never named keeps what it held
        $this->assertSame(
            [[self::QUALIFIED_GROUP, self::QUALIFIED_KEY, self::storedSlot([
                ['v' => '80'],
                ['v' => 'patched', 'p' => 1],
            ]), self::LANGUAGE]],
            $written
        );
        $this->assertSame(
            self::storedSlot([['v' => '176', 'var' => self::IMPERIAL]]),
            $stored[self::QUALIFIED_GROUP][self::LANGUAGE][self::OTHER_QUALIFIED_KEY]
        );
    }

    /**
     * Narrowing a declaration HIDES the entries it no longer permits - a read does not publish them
     * - and a write that does not mention them leaves them in the row: absence from a payload is
     * not a clear.
     *
     * The test that can fail is the last assertion: the ROW read after the PUT of exactly what the
     * GET returned, not the GET itself. Deleting the carry-over in the write path turns it red.
     *
     * @throws Exception
     */
    public function testAPutOfWhatAReadPublishedKeepsTheEntriesTheNarrowedDeclarationHides(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => self::storedSlot([
                ['v' => '80', 'var' => self::METRIC],
                ['v' => '176', 'var' => self::IMPERIAL],
            ]),
        ]]];

        // the declaration has narrowed: `imperial` has left it, and the stored imperial value stays
        $keyConfigs = [
            self::QUALIFIED_KEY => $this->createKeyConfig(['variants' => [self::METRIC], 'multiValue' => true]),
            self::PLAIN_KEY => $this->createKeyConfig(['fieldtype' => 'input']),
        ];

        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]);
        $adapter = $this->createQualifiedAdapter($keyConfigs, $stored, self::BOTH_CAPABILITIES);
        $fieldDefinition = $this->createQualifiedFieldDefinition($keyConfigs);

        $published = $adapter->getDetailData($this->makeEmpty(Concrete::class), $container, $fieldDefinition);
        $slot = $published[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY];

        $this->assertSame([['v' => '80', 'var' => self::METRIC]], $slot);

        $this->writeKeys($adapter, $container, $fieldDefinition, [self::QUALIFIED_KEY => $slot]);

        $this->assertSame(
            self::storedSlot([['v' => '80', 'var' => self::METRIC], ['v' => '176', 'var' => self::IMPERIAL]]),
            $stored[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]
        );

        // a PATCH of the same slot keeps it too, and an entry the write adds sits beside it
        $this->writeKeys(
            $adapter,
            $container,
            $fieldDefinition,
            [self::QUALIFIED_KEY => [['v' => '81', 'p' => 1]]],
            isPatch: true
        );

        $this->assertSame(
            self::storedSlot([
                ['v' => '80', 'var' => self::METRIC],
                ['v' => '81', 'p' => 1],
                ['v' => '176', 'var' => self::IMPERIAL],
            ]),
            $stored[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]
        );
    }

    /**
     * The case the test above does not reach: narrowing leaves NOTHING visible, so the read
     * publishes the empty entry list, and a PUT of exactly that has to be lossless as well.
     *
     * The empty list is no entry rather than one entry holding the empty array
     * ({@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueParserInterface::parseEntries()}),
     * so the row keeps the hidden entries and gains no default entry the client never sent - which
     * for a key of a scalar type would be the empty array handed to its own adapter.
     *
     * @throws Exception
     */
    public function testAPutOfTheEmptyListAReadPublishedKeepsEveryHiddenEntryAndAddsNone(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => self::storedSlot([
                ['v' => '176', 'var' => self::IMPERIAL],
                ['v' => '177', 'var' => self::IMPERIAL, 'p' => 1],
            ]),
        ]]];

        // every stored entry names a variant the declaration no longer carries
        $keyConfigs = $this->qualifiedKeyConfigs(['variants' => [self::METRIC], 'multiValue' => true]);
        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]);
        $adapter = $this->createQualifiedAdapter($keyConfigs, $stored, self::BOTH_CAPABILITIES);
        $fieldDefinition = $this->createQualifiedFieldDefinition($keyConfigs);

        $published = $adapter->getDetailData($this->makeEmpty(Concrete::class), $container, $fieldDefinition);
        $slot = $published[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY];

        $this->assertSame([], $slot);

        $this->writeKeys($adapter, $container, $fieldDefinition, [self::QUALIFIED_KEY => $slot]);

        $this->assertSame(
            self::storedSlot([
                ['v' => '176', 'var' => self::IMPERIAL],
                ['v' => '177', 'var' => self::IMPERIAL, 'p' => 1],
            ]),
            $stored[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]
        );
    }

    /**
     * The sibling of the case above, where there is nothing hidden either: a write clearing every
     * entry of a declaring key leaves the slot holding the empty entry list, and a read of it
     * publishes the empty list it was handed.
     *
     * Not nothing: a slot nothing was ever written to is where the key's configured default
     * applies, and a cleared key must not be refilled from it
     * ({@see self::testClearingAKeyWithADefaultIsNotAnsweredByTheNextReadWithThatDefault()}).
     *
     * `[]` can be stored at this slot and mean the empty entry list because the key's declaration
     * says which shape the slot is in. At the slot of a key declaring nothing the same `[]` is that
     * key's own empty value - the multi-select selecting nothing - and is read as one, which
     * `QualifiedValueSerializerTest::testAValueOfAKeyDeclaringNothingIsNeverReadAsEntries()` holds.
     *
     * @throws Exception
     */
    public function testAWriteOfTheEmptyListStoresTheEmptyEntryListWhereNothingIsHidden(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => self::storedSlot([['v' => '80'], ['v' => '176', 'var' => self::IMPERIAL]]),
        ]]];

        $written = $this->writeAndRecord(
            $this->qualifiedKeyConfigs(),
            [self::QUALIFIED_KEY => []],
            $stored,
            capabilities: self::BOTH_CAPABILITIES
        );

        $this->assertSame(
            [[self::QUALIFIED_GROUP, self::QUALIFIED_KEY, self::storedSlot([]), self::LANGUAGE]],
            $written
        );
    }

    /**
     * A key with a configured default, cleared: the next read publishes the empty list and not that
     * default, so a PUT of what it publishes adds nothing the clearing request never supplied.
     *
     * `quantityValue` and `inputQuantityValue` are the two key types that carry a default
     * ({@see ClassificationStoreAdapter::getDefaultValue()}), and the default is applied to a slot
     * the row holds nothing at - which, after a clearing write, this slot is not.
     *
     * Both arms are asserted over the one key, because the default has to keep applying where it
     * always did: a slot nothing was ever written to still publishes it, as the entry the
     * declaration makes it.
     *
     * @throws Exception
     */
    public function testClearingAKeyWithADefaultIsNotAnsweredByTheNextReadWithThatDefault(): void
    {
        $stored = [];
        $keyConfigs = $this->qualifiedKeyConfigs();
        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]);
        $adapter = $this->createQualifiedAdapter(
            $keyConfigs,
            $stored,
            self::BOTH_CAPABILITIES,
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getFieldDefinitionFromKeyConfig' => $this->make(QuantityValueDefinition::class, [
                    'getName' => 'weight',
                    'getDefaultValue' => 5.0,
                    'getDefaultUnit' => null,
                ]),
            ])
        );
        $fieldDefinition = $this->createQualifiedFieldDefinition($keyConfigs);

        // nothing was ever written: the key's default applies, as the entry the declaration makes it
        $published = $adapter->getDetailData($this->makeEmpty(Concrete::class), $container, $fieldDefinition);
        $slot = $published[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY];

        $this->assertCount(1, $slot);
        $this->assertInstanceOf(QuantityValue::class, $slot[0]['v']);
        $this->assertSame(5.0, $slot[0]['v']->getValue());

        // and a write clearing the key is answered with the empty list rather than with it again
        $this->writeKeys($adapter, $container, $fieldDefinition, [self::QUALIFIED_KEY => []]);

        $this->assertSame(
            self::storedSlot([]),
            $stored[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]
        );

        $published = $adapter->getDetailData($this->makeEmpty(Concrete::class), $container, $fieldDefinition);

        $this->assertSame([], $published[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]);
    }

    /**
     * The declaration decides on the write as it does on the read, with no leniency for what the
     * row holds: the hidden variant is refused when a write names it, as is one never declared.
     *
     * @throws Exception
     */
    public function testAWriteNamingAnEntryTheDeclarationHidesIsRefused(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => self::storedSlot([['v' => '176', 'var' => self::IMPERIAL]]),
        ]]];
        $keyConfigs = $this->qualifiedKeyConfigs(['variants' => [self::METRIC], 'multiValue' => true]);
        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]);
        $adapter = $this->createQualifiedAdapter($keyConfigs, $stored, self::BOTH_CAPABILITIES);

        foreach ([self::IMPERIAL, 'nautical'] as $variant) {
            try {
                $this->writeKeys(
                    $adapter,
                    $container,
                    $this->createQualifiedFieldDefinition($keyConfigs),
                    [self::QUALIFIED_KEY => [['v' => '7', 'var' => $variant]]]
                );
                $this->fail('Expected the write to be refused.');
            } catch (AbstractApiException $exception) {
                $this->assertSame(HttpResponseErrorKeys::UNDECLARED_VARIANT->value, $exception->getErrorKey());
            }
        }
    }

    /**
     * A key that has stopped declaring both members serializes as the single value its row holds,
     * whatever that is: the declaration alone decides the shape, and this declaration says the slot
     * holds one value, so the list the row still holds is that value and is published as it stands.
     *
     * **This is the boundary of the rule, and it is asserted rather than left to be found.** The
     * entries the removed declaration hid are not read as entries any more, so they are neither
     * published as entries nor found by the next write to be carried over - a PUT stores the value
     * it supplied and nothing else. Narrowing a declaration *within* itself keeps hiding and
     * keeping them, which is what core's `testNarrowingADeclarationKeepsTheValuesItHides` settles
     * and what {@see self::testAPutOfWhatAReadPublishedKeepsTheEntriesTheNarrowedDeclarationHides()}
     * holds here.
     *
     * The alternative is the rule this ticket removed - deciding the shape by what the value looks
     * like - under which an ordinary table value was read as an entry list and reduced to its first
     * cell for a key that declares nothing at all.
     *
     * Asserted on the row, so that neither half can pass on the slot travelling through untouched.
     *
     * @throws Exception
     */
    public function testAKeyThatStoppedDeclaringSerialisesTheOneValueItsRowHolds(): void
    {
        $list = [
            ['v' => '176', 'var' => self::IMPERIAL],
            ['v' => '80', 'p' => 0],
        ];
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => $list,
        ]]];

        $keyConfigs = $this->qualifiedKeyConfigs(['fieldtype' => 'input']);

        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]);
        $adapter = $this->createQualifiedAdapter($keyConfigs, $stored, self::BOTH_CAPABILITIES);
        $fieldDefinition = $this->createQualifiedFieldDefinition($keyConfigs);

        $published = $adapter->getDetailData($this->makeEmpty(Concrete::class), $container, $fieldDefinition);

        $this->assertSame($list, $published[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]);

        // a PUT stores the one value it supplied, as the slot of a key declaring nothing has always
        // stored it
        $this->writeKeys($adapter, $container, $fieldDefinition, [self::QUALIFIED_KEY => '99']);
        $this->assertSame('99', $stored[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]);
    }

    /**
     * The sibling: a row holding only entries the *declaration it still has* hides publishes
     * nothing, rather than falling back to the first entry it holds, and a write adds the default
     * entry beside them. The key declares a variant it does not hold, so its slot is still read as
     * the entry list it is - which is what tells this case from
     * {@see self::testAKeyThatStoppedDeclaringSerialisesTheOneValueItsRowHolds()}.
     *
     * @throws Exception
     */
    public function testAKeyPublishesNoHiddenEntryAsItsValue(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => self::storedSlot([['v' => '176', 'var' => self::IMPERIAL]]),
        ]]];
        $keyConfigs = $this->qualifiedKeyConfigs(['variants' => [self::METRIC]]);

        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]);
        $adapter = $this->createQualifiedAdapter($keyConfigs, $stored, self::BOTH_CAPABILITIES);
        $fieldDefinition = $this->createQualifiedFieldDefinition($keyConfigs);

        $published = $adapter->getDetailData($this->makeEmpty(Concrete::class), $container, $fieldDefinition);
        $this->assertSame([], $published[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]);

        $this->writeKeys($adapter, $container, $fieldDefinition, [self::QUALIFIED_KEY => [['v' => '80']]]);
        $this->assertSame(
            self::storedSlot([['v' => '80'], ['v' => '176', 'var' => self::IMPERIAL]]),
            $stored[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]
        );
    }

    /**
     * The sibling of the patch test above, for the key that declares nothing any more: a patch it
     * does not name is not refused for what its row holds - the engine here reports nothing, which
     * the carried-over second position would need - and the hidden entries are still in the row.
     *
     * @throws Exception
     */
    public function testAPatchIsNotRefusedByAStoppedDeclaringKeyItNeverSuppliedAndKeepsItsRow(): void
    {
        $keyConfigs = [
            self::QUALIFIED_KEY => $this->createKeyConfig([
                'variants' => [self::METRIC, self::IMPERIAL],
                'multiValue' => true,
            ]),
            // the declaration is gone, and the two values the slot holds have stayed
            self::OTHER_QUALIFIED_KEY => $this->createKeyConfig(['fieldtype' => 'input']),
        ];

        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => self::storedSlot([['v' => '80']]),
            self::OTHER_QUALIFIED_KEY => self::storedSlot([['v' => 'a'], ['v' => 'b', 'p' => 1]]),
        ]]];

        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]);
        $adapter = $this->createQualifiedAdapter($keyConfigs, $stored, capabilities: []);

        $this->writeKeys(
            $adapter,
            $container,
            $this->createQualifiedFieldDefinition($keyConfigs),
            [self::QUALIFIED_KEY => [['v' => 'patched']]],
            isPatch: true
        );

        $this->assertSame(
            self::storedSlot([['v' => 'patched']]),
            $stored[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]
        );
        // the key the patch never named was not written at all, so whatever its row holds is
        // untouched - which is how a patch leaves a slot it does not address
        $this->assertSame(
            [['v' => 'a'], ['v' => 'b', 'p' => 1]],
            $stored[self::QUALIFIED_GROUP][self::LANGUAGE][self::OTHER_QUALIFIED_KEY]
        );
    }

    /**
     * No entry of a qualified slot reaches the key's own type adapter as a patch.
     *
     * The patch of such a slot is resolved by qualifier before the entries are converted, so what
     * each one then carries is one whole value of the key's type and not the partial envelope an
     * adapter in patch mode reads - and the entries the merge carries over from the row were never
     * supplied by this request at all.
     *
     * @throws Exception
     */
    public function testNoEntryOfAQualifiedSlotIsHandedToTheKeysAdapterAsAPatch(): void
    {
        $qualified = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => self::storedSlot([
                ['v' => 'first'],
                ['v' => 'second', 'p' => 1],
                ['v' => 'third', 'p' => 2],
            ]),
        ]]];

        // one flag, because one entry was supplied: the two the merge carries over from the row
        // never reach the key's own type adapter at all, which is asserted over the value they keep
        // in testAPatchOfOneRelationEntryLeavesAnUntouchedSiblingRawInTheRow()
        $this->assertSame(
            [false],
            $this->recordPatchFlagsSeenBySubAdapter(
                $qualified,
                [self::QUALIFIED_KEY => [['v' => 'patched', 'p' => 1]]]
            )
        );

        // the sibling arm: a key that declares nothing still hands the write's own patch flag through
        $plain = [self::QUALIFIED_GROUP => [self::LANGUAGE => [self::PLAIN_KEY => '0']]];

        $this->assertSame(
            [true],
            $this->recordPatchFlagsSeenBySubAdapter($plain, [self::PLAIN_KEY => 'patched'])
        );
    }

    /**
     * What a grid cell shows for a slot serving a list, read through the one place that decides it,
     * so that the cell and the search preview cannot disagree on which value they show.
     *
     * @throws Exception
     */
    public function testTheSearchPreviewShowsTheDefaultSlotOfAQualifiedKey(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => self::storedSlot([
                ['v' => '176', 'var' => self::IMPERIAL],
                ['v' => '80'],
            ]),
        ]]];

        $keyConfigs = $this->qualifiedKeyConfigs();
        $adapter = $this->createQualifiedAdapter($keyConfigs, $stored);

        $data = $adapter->getPreviewFieldData(
            $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]),
            $this->createQualifiedFieldDefinition($keyConfigs),
            []
        );

        $this->assertSame('80', $data[self::PREVIEW_FIELD]['Measurements - key-' . self::QUALIFIED_KEY]);
    }

    /**
     * The third consumer of the slot: the inheritance walk reads an ancestor's slot and resolves it
     * through the key's own type, which cannot read the entry list - so for a key declaring
     * qualifiers the origin is still reported and the value behind it is not resolved. Its sibling
     * arm, the key that declares nothing, keeps resolving it.
     *
     * @throws Exception
     */
    public function testTheInheritedValueOfAQualifiedSlotIsNotResolvedThroughTheKeysOwnType(): void
    {
        $resolved = [];
        $keyConfigs = $this->qualifiedKeyConfigs();
        $stored = [];
        $adapter = $this->createQualifiedAdapter(
            $keyConfigs,
            $stored,
            inheritanceService: $this->makeEmpty(InheritanceServiceInterface::class, [
                'getFieldInheritanceData' => static function (
                    Concrete $object,
                    Data $fieldDefinition,
                    string $key,
                    ?FieldContextData $contextData = null
                ) use (&$resolved): InheritanceData {
                    $resolved[(int) $contextData?->getClassificationStoreKeyId()] =
                        $contextData?->shouldResolveInheritedValue();

                    return new InheritanceData(1);
                },
            ])
        );

        $adapter->getFieldInheritance(
            $this->makeEmpty(Concrete::class, [
                'get' => $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]),
            ]),
            $this->createQualifiedFieldDefinition($keyConfigs),
            'myStore',
            new FieldContextData(resolveInheritedValue: true)
        );

        $this->assertSame([self::QUALIFIED_KEY => false, self::PLAIN_KEY => true], $resolved);
    }

    /**
     * The sibling arm of that guard, the one that does resolve the value: the slot an ancestor holds
     * is read under the key's declaration and under nothing else, so the declaring key resolves the
     * default entry of its list and the key that declares nothing resolves the one value its row
     * holds - which is that whole list, because no shape a value has makes it entries for a key
     * declaring none. Asserted on the value the walk reads, which is both what gets resolved and
     * what decides whether the element holds a value of its own at all.
     *
     * @throws Exception
     */
    public function testTheInheritedValueOfEachKeyIsReadUnderItsOwnDeclaration(): void
    {
        $read = [];
        $list = self::storedSlot([['v' => '80'], ['v' => '176', 'var' => self::IMPERIAL]]);
        // the plain key's row holds the list of a declaration since taken away: it is read as the
        // one value the key now holds, and nothing of it is deleted by being read
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [
            self::QUALIFIED_KEY => $list,
            self::PLAIN_KEY => $list,
        ]]];
        $keyConfigs = $this->qualifiedKeyConfigs();
        $adapter = $this->createQualifiedAdapter(
            $keyConfigs,
            $stored,
            inheritanceService: $this->makeEmpty(InheritanceServiceInterface::class, [
                'getFieldInheritanceData' => static function (
                    Concrete $object,
                    Data $fieldDefinition,
                    string $key,
                    ?FieldContextData $contextData = null
                ) use (&$read): InheritanceData {
                    $read[(int) $contextData?->getClassificationStoreKeyId()] =
                        $contextData?->getFieldValueFromContextObject($key);

                    return new InheritanceData(1);
                },
            ])
        );

        $adapter->getFieldInheritance(
            $this->makeEmpty(Concrete::class, [
                'get' => $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]),
            ]),
            $this->createQualifiedFieldDefinition($keyConfigs),
            'myStore',
            new FieldContextData(resolveInheritedValue: true)
        );

        // one rule for the whole walk, asked of each key's own declaration: the declaring key's
        // slot reduces to the default entry, and the key declaring nothing holds the one value its
        // row holds
        $this->assertSame([self::QUALIFIED_KEY => '80', self::PLAIN_KEY => $list], $read);
    }

    /**
     * Two entries of a key whose type is an encrypted field, written through a type adapter that
     * hands the value back wrapped - or, which is what the guard is about, unwrapped.
     *
     * @param array<int, array<int, mixed>> $written
     *
     * @return array<int, array<int, mixed>>
     *
     * @throws Exception
     */
    private function writeEncryptedEntries(bool $wrapped, array &$written = []): array
    {
        $delegate = $this->makeEmpty(Data::class);

        return $this->writeAndRecord(
            $this->qualifiedKeyConfigs(),
            [self::QUALIFIED_KEY => [
                ['v' => '80'],
                ['v' => '176', 'var' => self::IMPERIAL],
            ]],
            capabilities: self::BOTH_CAPABILITIES,
            written: $written,
            dataAdapterService: $this->makeEmpty(DataAdapterServiceInterface::class, [
                'tryDataAdapter' => $this->makeEmpty(SetterDataInterface::class, [
                    'getDataForSetter' => static fn (
                        Concrete $element,
                        Data $fieldDefinition,
                        string $key,
                        array $data
                    ): mixed => $wrapped ? new EncryptedField($delegate, $data[$key]) : $data[$key],
                ]),
            ]),
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getFieldDefinitionFromKeyConfig' => $this->make(EncryptedFieldDefinition::class, [
                    'getName' => 'secret',
                ]),
            ])
        );
    }

    /**
     * The patch flag the key's own type adapter was handed for each value it converted, in the order
     * it converted them, for a patch of the supplied keys.
     *
     * @param array<int, array<string, array<int, mixed>>> $stored
     * @param array<int, mixed> $keys
     *
     * @return array<int, bool>
     *
     * @throws Exception
     */
    private function recordPatchFlagsSeenBySubAdapter(array $stored, array $keys): array
    {
        $seen = [];
        $keyConfigs = $this->qualifiedKeyConfigs();
        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]);

        $adapter = $this->createQualifiedAdapter(
            $keyConfigs,
            $stored,
            self::BOTH_CAPABILITIES,
            dataAdapterService: $this->makeEmpty(DataAdapterServiceInterface::class, [
                'tryDataAdapter' => $this->makeEmpty(SetterDataInterface::class, [
                    'getDataForSetter' => static function (
                        Concrete $element,
                        Data $fieldDefinition,
                        string $key,
                        array $data,
                        UserInterface $user,
                        ?FieldContextData $contextData = null,
                        bool $isPatch = false
                    ) use (&$seen): mixed {
                        $seen[] = $isPatch;

                        return $data[$key];
                    },
                ]),
            ])
        );

        $this->writeKeys(
            $adapter,
            $container,
            $this->createQualifiedFieldDefinition($keyConfigs),
            $keys,
            isPatch: true
        );

        return $seen;
    }

    /**
     * The supplied keys of one group written through the write path, over a container a caller holds
     * already - as opposed to {@see self::writeAndRecord()}, which makes its own.
     *
     * @param array<int, mixed> $keys
     *
     * @throws Exception
     */
    private function writeKeys(
        ClassificationStoreAdapter $adapter,
        Classificationstore $container,
        ClassificationstoreDefinition $fieldDefinition,
        array $keys,
        bool $isPatch = false
    ): void {
        $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class, ['get' => $container]),
            $fieldDefinition,
            'myStore',
            ['myStore' => [
                self::QUALIFIED_GROUP => [self::LANGUAGE => $keys],
                'activeGroups' => [self::QUALIFIED_GROUP => true],
            ]],
            $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
            isPatch: $isPatch
        );
    }

    /**
     * The current value the key's own type adapter was handed for each entry it converted, in the
     * order the entries were converted.
     *
     * @param array<int, array<string, array<int, mixed>>> $stored
     * @param array<int, mixed> $keys
     *
     * @return array<int, mixed>
     *
     * @throws Exception
     */
    private function recordCurrentValuesSeenBySubAdapter(array &$stored, array $keys): array
    {
        $seen = [];
        $keyConfigs = $this->qualifiedKeyConfigs();
        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]);

        $subAdapter = $this->makeEmpty(SetterDataInterface::class, [
            'getDataForSetter' => static function (
                Concrete $element,
                Data $fieldDefinition,
                string $key,
                array $data,
                UserInterface $user,
                ?FieldContextData $contextData = null
            ) use (&$seen): mixed {
                $seen[] = $contextData?->getFieldValueFromContextObject($key);

                return $data[$key];
            },
        ]);

        $adapter = $this->createQualifiedAdapter(
            $keyConfigs,
            $stored,
            self::BOTH_CAPABILITIES,
            dataAdapterService: $this->makeEmpty(DataAdapterServiceInterface::class, [
                'tryDataAdapter' => $subAdapter,
            ])
        );

        $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class, ['get' => $container]),
            $this->createQualifiedFieldDefinition($keyConfigs),
            'myStore',
            ['myStore' => [
                self::QUALIFIED_GROUP => [self::LANGUAGE => $keys],
                'activeGroups' => [self::QUALIFIED_GROUP => true],
            ]],
            $this->makeEmpty(UserInterface::class, ['isAdmin' => true])
        );

        return $seen;
    }

    /**
     * A language service answering one set for viewing and another for editing, which is what a
     * user who may edit a language and may not view it looks like.
     *
     * @param string[] $viewable
     * @param string[] $editable
     *
     * @throws Exception
     */
    private function createLanguageService(array $viewable, array $editable): LanguageServiceInterface
    {
        return $this->makeEmpty(LanguageServiceInterface::class, [
            'getUserAllowedLanguagesWithLanguageIndependentValue' => static fn (
                Concrete $dataObject,
                UserInterface $user,
                string $permission
            ): array => $permission === ElementPermissions::LANGUAGE_EDIT_PERMISSIONS ? $editable : $viewable,
        ]);
    }

    /**
     * The key configurations of a store whose keys permit only the single default slot.
     *
     * @param array<string, mixed> $declaration
     *
     * @return array<int, KeyConfig>
     */
    private function unqualifiedKeyConfigs(array $declaration = ['fieldtype' => 'input']): array
    {
        return [
            self::PLAIN_KEY => $this->createKeyConfig($declaration),
            self::OTHER_PLAIN_KEY => $this->createKeyConfig($declaration),
        ];
    }

    /**
     * @param array<string, mixed>|null $declaration
     *
     * @return array<int, KeyConfig>
     */
    private function qualifiedKeyConfigs(?array $declaration = null): array
    {
        $declaration ??= ['variants' => [self::METRIC, self::IMPERIAL], 'multiValue' => true];

        return [
            self::QUALIFIED_KEY => $this->createKeyConfig($declaration),
            self::PLAIN_KEY => $this->createKeyConfig(['fieldtype' => 'input']),
        ];
    }

    /**
     * The slot as the row holds it at a key that declares qualifiers: the entry list, which is the
     * same list a read publishes and a write supplies. Named rather than spelled out, so that a
     * fixture meant as a *stored* slot says so where the declaration it is read under is set up
     * lines away.
     *
     * @param array<int, array<string, mixed>> $entries
     *
     * @return array<int, array<string, mixed>>
     */
    private static function storedSlot(array $entries): array
    {
        return $entries;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function createKeyConfig(array $definition): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setName('weight');
        $keyConfig->setDefinition((string) json_encode($definition));

        return $keyConfig;
    }

    /**
     * Writes the supplied keys of one group into a container and hands back every
     * setLocalizedKeyValue call it made, as (groupId, keyId, value, language).
     *
     * @param array<int, KeyConfig> $keyConfigs
     * @param array<int, mixed> $keys
     * @param array<int, array<string, array<int, mixed>>> $stored
     * @param string[]|null $capabilities
     * @param array<int, array<int, mixed>> $written
     *
     * @return array<int, array<int, mixed>>
     *
     * @throws Exception
     */
    private function writeAndRecord(
        array $keyConfigs,
        array $keys,
        array $stored = [],
        bool $isPatch = false,
        ?array $capabilities = null,
        array &$written = [],
        ?DataAdapterServiceInterface $dataAdapterService = null,
        ?ServiceResolverInterface $serviceResolver = null
    ): array {
        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true], $written);
        $adapter = $this->createQualifiedAdapter(
            $keyConfigs,
            $stored,
            $capabilities,
            $dataAdapterService,
            serviceResolver: $serviceResolver
        );

        $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class, ['get' => $container]),
            $this->createQualifiedFieldDefinition($keyConfigs),
            'myStore',
            ['myStore' => [
                self::QUALIFIED_GROUP => [self::LANGUAGE => $keys],
                'activeGroups' => [self::QUALIFIED_GROUP => true],
            ]],
            $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
            isPatch: $isPatch
        );

        return $written;
    }

    /**
     * The supplied keys written through the write path and read back through the read path, over the
     * one container, so that the two halves are asserted to agree rather than separately.
     *
     * @param array<int, array<string, array<int, mixed>>> $stored
     * @param array<int, mixed> $keys
     *
     * @return array<int|string, mixed>
     *
     * @throws Exception
     */
    private function roundTrip(array &$stored, array $keys): array
    {
        $keyConfigs = $this->qualifiedKeyConfigs();
        $container = $this->createRecordingContainer($stored, [self::QUALIFIED_GROUP => true]);
        $adapter = $this->createQualifiedAdapter($keyConfigs, $stored, self::BOTH_CAPABILITIES);
        $fieldDefinition = $this->createQualifiedFieldDefinition($keyConfigs);

        $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class, ['get' => $container]),
            $fieldDefinition,
            'myStore',
            ['myStore' => [
                self::QUALIFIED_GROUP => [self::LANGUAGE => $keys],
                'activeGroups' => [self::QUALIFIED_GROUP => true],
            ]],
            $this->makeEmpty(UserInterface::class, ['isAdmin' => true])
        );

        return $adapter->getDetailData($this->makeEmpty(Concrete::class), $container, $fieldDefinition) ?? [];
    }

    /**
     * @param array<int, KeyConfig> $keyConfigs
     *
     * @throws Exception
     */
    private function createQualifiedFieldDefinition(
        array $keyConfigs,
        bool $isLocalized = false
    ): ClassificationstoreDefinition {
        return $this->make(ClassificationstoreDefinition::class, [
            'isLocalized' => $isLocalized,
            'getStoreId' => self::STORE_ID,
            'getKeyConfiguration' => static fn (int $keyId): KeyConfig => $keyConfigs[$keyId],
            'recursiveGetActiveGroupsIds' => [self::QUALIFIED_GROUP => true],
        ]);
    }

    /**
     * The storage boundary, held in the array handed in: the values this suite has no database for
     * go in on setLocalizedKeyValue and come back out on getLocalizedKeyValue, so a read asserts
     * what the write actually left behind.
     *
     * @param array<int, array<string, array<int, mixed>>> $stored
     * @param array<int, bool> $activeGroups
     * @param array<int, array<int, mixed>> $written
     *
     * @throws Exception
     */
    private function createRecordingContainer(
        array &$stored,
        array $activeGroups,
        array &$written = []
    ): Classificationstore {
        $container = $this->make(Classificationstore::class, [
            'getObject' => $this->makeEmpty(Concrete::class),
            'getActiveGroups' => static fn (): array => $activeGroups,
            'getGroupCollectionMappings' => [],
            'getGroupIdsWithData' => [],
            'setActiveGroups' => null,
            'setGroupCollectionMappings' => null,
            'setLocalizedKeyValue' => function (
                int $groupId,
                int $keyId,
                mixed $value,
                ?string $language = null
            ) use (&$stored, &$written, &$container) {
                $written[] = [$groupId, $keyId, $value, $language];
                $stored[$groupId][$language][$keyId] = $value;

                return $container;
            },
            'getLocalizedKeyValue' => static function (
                int $groupId,
                int $keyId,
                ?string $language = null
            ) use (&$stored): mixed {
                return $stored[$groupId][$language][$keyId] ?? null;
            },
        ]);

        return $container;
    }

    /**
     * @param array<int, KeyConfig> $keyConfigs
     * @param array<int, array<string, array<int, mixed>>> $stored
     * @param string[]|null $capabilities
     *
     * @throws Exception
     */
    private function createQualifiedAdapter(
        array $keyConfigs,
        array &$stored,
        ?array $capabilities = null,
        ?DataAdapterServiceInterface $dataAdapterService = null,
        ?LanguageServiceInterface $languageService = null,
        ?ServiceResolverInterface $serviceResolver = null,
        ?InheritanceServiceInterface $inheritanceService = null,
        ?DataServiceInterface $dataService = null
    ): ClassificationStoreAdapter {
        $relations = [];
        foreach (array_keys($keyConfigs) as $keyId) {
            $relations[$keyId] = $this->createRelation($keyId, self::QUALIFIED_GROUP);
        }

        $requestedGroupIds = [];

        return $this->createAdapter(
            definitionCacheResolver: $this->makeEmpty(DefinitionCacheResolverInterface::class, [
                'get' => static fn (int $keyId): ?KeyConfig => $keyConfigs[$keyId] ?? null,
            ]),
            dataAdapterService: $dataAdapterService ?? $this->makeEmpty(DataAdapterServiceInterface::class, [
                // the key's own type adapter: one value in, one value out
                'tryDataAdapter' => $this->makeEmpty(SetterDataInterface::class, [
                    'getDataForSetter' => static fn (
                        Concrete $element,
                        Data $fieldDefinition,
                        string $key,
                        array $data
                    ): mixed => $data[$key],
                ]),
            ]),
            dataService: $dataService ?? $this->makeEmpty(DataServiceInterface::class, [
                'getNormalizedValue' => static fn (mixed $value): mixed => $value,
                'getPreviewFieldData' => static fn (
                    mixed $value,
                    Data $fieldDefinition,
                    array $data
                ): array => array_merge($data, [self::PREVIEW_FIELD => $value]),
                'getPreviewFieldName' => self::PREVIEW_FIELD,
            ]),
            groupConfigResolver: $this->makeEmpty(GroupConfigResolverInterface::class, [
                'getById' => static function (int $id): GroupConfig {
                    $groupConfig = new GroupConfig();
                    $groupConfig->setName('Measurements');

                    return $groupConfig;
                },
            ]),
            inheritanceService: $inheritanceService,
            keyRelationResolver: $this->createKeyRelationResolver(
                [self::QUALIFIED_GROUP => $relations],
                $requestedGroupIds
            ),
            languageService: $languageService,
            serviceResolver: $serviceResolver ?? $this->makeEmpty(ServiceResolverInterface::class, [
                'getFieldDefinitionFromKeyConfig' => $this->makeEmpty(Data::class, [
                    'getName' => 'inputField',
                ]),
            ]),
            storeEngineResolver: $this->createStoreEngineResolver($capabilities),
        );
    }

    /**
     * @param array<int, string> $allowedLanguages
     *
     * @return array<int, string|null>
     *
     * @throws Exception
     */
    private function getStoredLanguagesForNonAdmin(array $allowedLanguages): array
    {
        $storedLanguages = [];
        $existingContainer = $this->make(Classificationstore::class, [
            'setLocalizedKeyValue' => function (
                int $groupId,
                int $keyId,
                mixed $value,
                ?string $language = null
            ) use (&$storedLanguages, &$existingContainer) {
                $storedLanguages[] = $language;

                return $existingContainer;
            },
        ]);

        $element = $this->makeEmpty(Concrete::class, [
            'get' => $existingContainer,
        ]);

        $fieldDefinition = $this->make(ClassificationstoreDefinition::class, [
            'isLocalized' => true,
            'getKeyConfiguration' => new KeyConfig(),
        ]);

        $adapter = $this->createAdapter(
            dataAdapterService: $this->makeEmpty(DataAdapterServiceInterface::class, [
                'tryDataAdapter' => $this->makeEmpty(SetterDataInterface::class, [
                    'getDataForSetter' => 'new-value',
                ]),
            ]),
            languageService: $this->makeEmpty(LanguageServiceInterface::class, [
                'getUserAllowedLanguagesWithLanguageIndependentValue' => $allowedLanguages,
            ]),
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getFieldDefinitionFromKeyConfig' => $this->makeEmpty(Data::class, [
                    'getName' => 'inputField',
                ]),
            ]),
        );

        $adapter->getDataForSetter(
            $element,
            $fieldDefinition,
            'myStore',
            [
                'myStore' => [
                    22 => [
                        'default' => [2 => 'language-independent-value'],
                        'de' => [2 => 'german-value'],
                    ],
                    'activeGroups' => [22 => true],
                ],
            ],
            $this->makeEmpty(UserInterface::class, ['isAdmin' => false])
        );

        return $storedLanguages;
    }

    /**
     * @return array<int, KeyGroupRelation[]>
     */
    private function relationsOfTwoGroups(): array
    {
        return [
            10 => [
                51 => $this->createRelation(51, 10),
                7 => $this->createRelation(7, 10),
            ],
            20 => [
                33 => $this->createRelation(33, 20),
            ],
        ];
    }

    private function createRelation(int $keyId, int $groupId): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId($groupId);
        $relation->setName('key-' . $keyId);

        return $relation;
    }

    /**
     * @param array<int, bool> $activeGroups
     *
     * @throws Exception
     */
    private function createStore(array $activeGroups): Classificationstore
    {
        return $this->make(Classificationstore::class, [
            'getObject' => $this->makeEmpty(Concrete::class),
            'getActiveGroups' => $activeGroups,
            'getGroupCollectionMappings' => [],
            'getLocalizedKeyValue' => static fn (
                int $groupId,
                int $keyId,
                ?string $language = null
            ): string => sprintf('g%d-k%d-%s', $groupId, $keyId, $language),
        ]);
    }

    /**
     * @param array<int, KeyGroupRelation[]> $relationsByGroup
     * @param int[] $requestedGroupIds
     *
     * @throws Exception
     */
    private function createKeyRelationResolver(
        array $relationsByGroup,
        array &$requestedGroupIds
    ): KeyRelationResolverInterface {
        $resolver = $this->createMock(KeyRelationResolverInterface::class);
        $resolver
            ->method('getByGroups')
            ->willReturnCallback(
                static function (array $groupIds) use ($relationsByGroup, &$requestedGroupIds): array {
                    $requestedGroupIds = $groupIds;

                    $relations = [];
                    foreach ($groupIds as $groupId) {
                        $relations[$groupId] = $relationsByGroup[$groupId] ?? [];
                    }

                    return $relations;
                }
            );
        $resolver
            ->method('getByGroup')
            ->willReturnCallback(
                static fn (int $groupId): array => $relationsByGroup[$groupId] ?? []
            );

        return $resolver;
    }

    /**
     * @param array<int, KeyGroupRelation[]> $relationsByGroup
     * @param int[] $requestedGroupIds
     *
     * @throws Exception
     */
    private function createAdapterReadingRelations(
        array $relationsByGroup,
        array &$requestedGroupIds
    ): ClassificationStoreAdapter {
        return $this->createAdapter(
            definitionCacheResolver: $this->makeEmpty(DefinitionCacheResolverInterface::class, [
                'get' => new KeyConfig(),
            ]),
            dataService: $this->makeEmpty(DataServiceInterface::class, [
                'getNormalizedValue' => static fn (mixed $value): mixed => $value,
                'getPreviewFieldData' => static fn (
                    mixed $value,
                    Data $fieldDefinition,
                    array $data
                ): array => array_merge($data, [self::PREVIEW_FIELD => $value]),
                'getPreviewFieldName' => self::PREVIEW_FIELD,
            ]),
            groupConfigResolver: $this->makeEmpty(GroupConfigResolverInterface::class, [
                'getById' => static function (int $id): GroupConfig {
                    $groupConfig = new GroupConfig();
                    $groupConfig->setName(self::GROUP_NAMES[$id]);

                    return $groupConfig;
                },
            ]),
            keyRelationResolver: $this->createKeyRelationResolver($relationsByGroup, $requestedGroupIds),
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getFieldDefinitionFromKeyConfig' => $this->makeEmpty(Data::class, [
                    'getName' => 'inputField',
                ]),
            ]),
        );
    }

    private function createAdapter(
        ?DataObjectServiceResolverInterface $dataObjectServiceResolver = null,
        ?DefinitionCacheResolverInterface $definitionCacheResolver = null,
        ?DataAdapterServiceInterface $dataAdapterService = null,
        ?DataServiceInterface $dataService = null,
        ?GroupConfigResolverInterface $groupConfigResolver = null,
        ?InheritanceServiceInterface $inheritanceService = null,
        ?KeyRelationResolverInterface $keyRelationResolver = null,
        ?LanguageServiceInterface $languageService = null,
        ?ServiceResolverInterface $serviceResolver = null,
        ?SecurityServiceInterface $securityService = null,
        ?ToolResolverInterface $toolResolver = null,
        ?StoreEngineResolverInterface $storeEngineResolver = null,
    ): ClassificationStoreAdapter {
        // the real two: both are pure readers of the key's own declaration, and standing them in
        // would leave what `variants` and `multiValue` mean untested on this path
        $keyQualifierResolver = new KeyQualifierResolver();

        return new ClassificationStoreAdapter(
            $dataObjectServiceResolver ?? $this->makeEmpty(DataObjectServiceResolverInterface::class),
            $definitionCacheResolver ?? $this->makeEmpty(DefinitionCacheResolverInterface::class),
            $dataAdapterService ?? $this->makeEmpty(DataAdapterServiceInterface::class),
            $dataService ?? $this->makeEmpty(DataServiceInterface::class),
            $groupConfigResolver ?? $this->makeEmpty(GroupConfigResolverInterface::class),
            $inheritanceService ?? $this->makeEmpty(InheritanceServiceInterface::class),
            $keyQualifierResolver,
            $keyRelationResolver ?? $this->makeEmpty(KeyRelationResolverInterface::class),
            $languageService ?? $this->makeEmpty(LanguageServiceInterface::class),
            new QualifiedValueSerializer(
                $keyQualifierResolver,
                new QualifiedValueParser($keyQualifierResolver),
                new EngineCapabilityGuard()
            ),
            $serviceResolver ?? $this->makeEmpty(ServiceResolverInterface::class),
            $securityService ?? $this->makeEmpty(SecurityServiceInterface::class),
            $storeEngineResolver ?? $this->createStoreEngineResolver(null),
            $toolResolver ?? $this->makeEmpty(ToolResolverInterface::class),
        );
    }

    /**
     * @param string[]|null $capabilities
     *
     * @throws Exception
     */
    private function createStoreEngineResolver(?array $capabilities): StoreEngineResolverInterface
    {
        return $this->makeEmpty(StoreEngineResolverInterface::class, [
            'getCapabilitiesOfStore' => $capabilities,
        ]);
    }
}
