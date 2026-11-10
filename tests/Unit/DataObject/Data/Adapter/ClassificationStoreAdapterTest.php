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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\MultiValueServiceResolver;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField as EncryptedFieldDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\EncryptedField;
use Pimcore\Model\DataObject\Data\MultiValue;
use Pimcore\Model\DataObject\Exception\UnsupportedEngineFeatureException;
use Pimcore\Model\UserInterface;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function json_encode;
use function ksort;
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
     * Every blob of a key that is not of type `multiValue`, which holds exactly the one slot a key always had, so that the two
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
            // the key is not of type `multiValue`, so what its blob carries declares nothing
            'a variant beside the default one' => [['variants' => [self::METRIC, self::IMPERIAL]]],
            'a list of values per variant' => [['multiValue' => true]],
            'both, naming variants and a list' => [['variants' => [self::METRIC], 'multiValue' => true]],
        ];
    }

    /**
     * A `multiValue` key carries its values at the one slot as an ordered list of entries - the
     * default variant first, then each declared variant in the order the key declares it - read
     * through core's `getKeyValues()`, one call per slot. A key with no value is left out.
     *
     * @throws Exception
     */
    public function testAMultiValueKeyIsReadAsAnOrderedListOfEntries(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [self::QUALIFIED_KEY => '80']]];
        $slots = [self::QUALIFIED_GROUP => [self::QUALIFIED_KEY => [self::LANGUAGE => [
            '' => [1 => '81'],
            self::IMPERIAL => [0 => '176'],
        ]]]];
        $keyConfigs = $this->qualifiedKeyConfigs();
        $written = [];
        $writtenEntries = [];

        $detail = $this->createQualifiedAdapter($keyConfigs, $stored)->getDetailData(
            $this->makeEmpty(Concrete::class),
            $this->createRecordingContainer(
                $stored,
                [self::QUALIFIED_GROUP => true],
                $written,
                $writtenEntries,
                $slots
            ),
            $this->createQualifiedFieldDefinition($keyConfigs)
        );

        $this->assertSame(
            [
                ['v' => '80'],
                ['v' => '81', 'p' => 1],
                ['v' => '176', 'var' => self::IMPERIAL],
            ],
            $detail[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]
        );
        $this->assertArrayNotHasKey(self::PLAIN_KEY, $detail[self::QUALIFIED_GROUP][self::LANGUAGE]);
    }

    /**
     * A `multiValue` key written through the object-editor data path is stored through core's
     * `setKeyValues()` and read back through `getKeyValues()`, not as one list in the default slot: a
     * value written against a declared variant is readable by core's own qualified reader at that
     * variant, and `getLocalizedKeyValue()` answers with position 0 of the default one.
     *
     * Then one variant is cleared and is gone while the other is untouched, by a patch naming it
     * with no value and by a PUT that leaves it out.
     *
     * @throws Exception
     */
    public function testAMultiValueKeyIsStoredThroughSetKeyValuesAndReadBackThroughGetKeyValues(): void
    {
        $stored = [];
        $slots = [];
        $written = [];
        $writtenEntries = [];
        $keyConfigs = $this->qualifiedKeyConfigs();
        $container = $this->createRecordingContainer(
            $stored,
            [self::QUALIFIED_GROUP => true],
            $written,
            $writtenEntries,
            $slots
        );

        $this->writeLanguages($container, $keyConfigs, $stored, [self::LANGUAGE => [self::QUALIFIED_KEY => [
            ['v' => '78'],
            ['v' => '80', 'var' => self::METRIC],
            ['v' => '176', 'var' => self::IMPERIAL],
        ]]]);

        $this->assertSame([], $written, 'no list is written as the one value of the default slot');
        $this->assertCount(1, $writtenEntries);
        $this->assertSame(
            [['78', self::LANGUAGE, null, 0], ['80', self::LANGUAGE, self::METRIC, 0], ['176', self::LANGUAGE, self::IMPERIAL, 0]],
            $this->describeCoreEntries($writtenEntries[0])
        );
        $this->assertSame([0 => '78'], $container->getKeyValues(self::QUALIFIED_GROUP, self::QUALIFIED_KEY));
        $this->assertSame(
            [0 => '80'],
            $container->getKeyValues(self::QUALIFIED_GROUP, self::QUALIFIED_KEY, self::LANGUAGE, self::METRIC)
        );
        $this->assertSame(
            [0 => '176'],
            $container->getKeyValues(self::QUALIFIED_GROUP, self::QUALIFIED_KEY, self::LANGUAGE, self::IMPERIAL)
        );
        $this->assertSame('78', $container->getLocalizedKeyValue(self::QUALIFIED_GROUP, self::QUALIFIED_KEY));

        // a patch naming the imperial variant with no value clears it and leaves the others alone
        $this->writeLanguages(
            $container,
            $keyConfigs,
            $stored,
            [self::LANGUAGE => [self::QUALIFIED_KEY => [['v' => null, 'var' => self::IMPERIAL]]]],
            isPatch: true
        );

        $this->assertSame(
            [],
            $container->getKeyValues(self::QUALIFIED_GROUP, self::QUALIFIED_KEY, self::LANGUAGE, self::IMPERIAL)
        );
        $this->assertSame(
            [0 => '80'],
            $container->getKeyValues(self::QUALIFIED_GROUP, self::QUALIFIED_KEY, self::LANGUAGE, self::METRIC)
        );
        $this->assertSame([0 => '78'], $container->getKeyValues(self::QUALIFIED_GROUP, self::QUALIFIED_KEY));

        // a PUT leaving the metric variant out clears it, and the default one is replaced
        $this->writeLanguages(
            $container,
            $keyConfigs,
            $stored,
            [self::LANGUAGE => [self::QUALIFIED_KEY => [['v' => '79']]]]
        );

        $this->assertSame(
            [],
            $container->getKeyValues(self::QUALIFIED_GROUP, self::QUALIFIED_KEY, self::LANGUAGE, self::METRIC)
        );
        $this->assertSame([0 => '79'], $container->getKeyValues(self::QUALIFIED_GROUP, self::QUALIFIED_KEY));
    }

    /**
     * Writing language A and then language B to one `multiValue` key leaves both stored, for the
     * declared variant and for the default one: in one request and in two successive ones.
     *
     * @throws Exception
     */
    public function testWritingTwoLanguagesOfOneMultiValueKeyLeavesBothStored(): void
    {
        $german = [self::GERMAN => [self::QUALIFIED_KEY => [
            ['v' => 'de-default'],
            ['v' => 'de-metric', 'var' => self::METRIC],
        ]]];
        $default = [self::LANGUAGE => [self::QUALIFIED_KEY => [
            ['v' => 'en-default'],
            ['v' => 'en-imperial', 'var' => self::IMPERIAL],
        ]]];

        $oneRequest = $this->writeAndReadLanguages([$default + $german]);
        $twoRequests = $this->writeAndReadLanguages([$default, $german]);

        foreach ([$oneRequest, $twoRequests] as $container) {
            $this->assertSame([0 => 'en-default'], $container(self::LANGUAGE, null));
            $this->assertSame([0 => 'en-imperial'], $container(self::LANGUAGE, self::IMPERIAL));
            $this->assertSame([0 => 'de-default'], $container(self::GERMAN, null));
            $this->assertSame([0 => 'de-metric'], $container(self::GERMAN, self::METRIC));
            // and neither write put a value into the other's slot of the other variant
            $this->assertSame([], $container(self::LANGUAGE, self::METRIC));
            $this->assertSame([], $container(self::GERMAN, self::IMPERIAL));
        }
    }

    /**
     * A patch carrying one entry changes the value at that position only and leaves the other values
     * of the key as they are, matched by qualifier and never by position in the array.
     *
     * @throws Exception
     */
    public function testAPatchOfOneEntryLeavesTheOtherValuesOfTheKeyIntact(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [self::QUALIFIED_KEY => 'first']]];
        $slots = [self::QUALIFIED_GROUP => [self::QUALIFIED_KEY => [self::LANGUAGE => [
            '' => [1 => 'second', 2 => 'third'],
        ]]]];
        $written = [];
        $writtenEntries = [];
        $container = $this->createRecordingContainer(
            $stored,
            [self::QUALIFIED_GROUP => true],
            $written,
            $writtenEntries,
            $slots
        );

        $this->writeLanguages(
            $container,
            $this->qualifiedKeyConfigs(),
            $stored,
            [self::LANGUAGE => [self::QUALIFIED_KEY => [['v' => 'patched', 'p' => 1]]]],
            isPatch: true
        );

        $this->assertSame(
            [0 => 'first', 1 => 'patched', 2 => 'third'],
            $container->getKeyValues(self::QUALIFIED_GROUP, self::QUALIFIED_KEY)
        );
    }

    /**
     * The merge base is the store, not the view of it the patch merges into: `$elementStore` is built
     * under the user's language *view* permissions, so a language this user may edit and may not view
     * is missing from it entirely, and merging against that absence would drop the values the write
     * leaves alone.
     *
     * @throws Exception
     */
    public function testAPatchKeepsTheValuesOfALanguageTheNormalizedViewCannotSee(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::GERMAN => [self::QUALIFIED_KEY => 'first']]];
        $slots = [self::QUALIFIED_GROUP => [self::QUALIFIED_KEY => [self::GERMAN => [
            '' => [1 => 'second', 2 => 'third'],
        ]]]];
        $written = [];
        $writtenEntries = [];
        $keyConfigs = $this->qualifiedKeyConfigs();
        $container = $this->createRecordingContainer(
            $stored,
            [self::QUALIFIED_GROUP => true],
            $written,
            $writtenEntries,
            $slots
        );

        $this->createQualifiedAdapter(
            $keyConfigs,
            $stored,
            self::BOTH_CAPABILITIES,
            languageService: $this->createLanguageService(viewable: [], editable: [self::GERMAN])
        )->getDataForSetter(
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
            [0 => 'first', 1 => 'patched', 2 => 'third'],
            $container->getKeyValues(self::QUALIFIED_GROUP, self::QUALIFIED_KEY, self::GERMAN)
        );
    }

    /**
     * A patch takes only the entries the request supplied through the key's own type adapter, and
     * what it carries over from the store it carries over as it is.
     *
     * The real {@see ManyToManyRelationAdapter} is driven here, because the two shapes that have to
     * disagree are its own: `normalize()` answers with {@see RelatedElementData} objects and its
     * setter reads `$relation['type']` off each element it is handed, so an untouched sibling taken
     * through the setter a second time would take the whole patch down.
     *
     * @throws Exception
     */
    public function testAPatchOfOneRelationEntryLeavesAnUntouchedSiblingAsItIs(): void
    {
        $sibling = [['type' => 'object', 'id' => 8]];
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [self::QUALIFIED_KEY => [['type' => 'object', 'id' => 7]]]]];
        $slots = [self::QUALIFIED_GROUP => [self::QUALIFIED_KEY => [self::LANGUAGE => [self::IMPERIAL => [0 => $sibling]]]]];
        $relationAdapter = $this->createRelationAdapter();
        $keyConfigs = $this->qualifiedKeyConfigs();
        $written = [];
        $writtenEntries = [];
        $container = $this->createRecordingContainer(
            $stored,
            [self::QUALIFIED_GROUP => true],
            $written,
            $writtenEntries,
            $slots
        );

        $adapter = $this->createQualifiedAdapter(
            $keyConfigs,
            $stored,
            self::BOTH_CAPABILITIES,
            dataAdapterService: $this->makeEmpty(DataAdapterServiceInterface::class, [
                'tryDataAdapter' => $relationAdapter,
            ]),
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getFieldDefinitionFromKeyConfig' => $this->makeEmpty(Data::class, ['getName' => 'related']),
            ])
        );

        $this->writeKeys(
            $adapter,
            $container,
            $this->createQualifiedFieldDefinition($keyConfigs),
            [self::QUALIFIED_KEY => [['v' => [['type' => 'object', 'id' => 9]]]]],
            isPatch: true
        );

        $this->assertCount(1, $writtenEntries);
        $entries = $writtenEntries[0];
        $this->assertCount(2, $entries);

        // the one entry the patch named went through the relation setter
        $this->assertNull($entries[0]->getVariant());
        $this->assertSame([9], array_map(
            static fn (Concrete $element): int => $element->getId(),
            $entries[0]->getValue()
        ));
        // and the untouched sibling is the value the store held
        $this->assertSame(self::IMPERIAL, $entries[1]->getVariant());
        $this->assertSame($sibling, $entries[1]->getValue());
    }

    /**
     * The value of a `multiValue` key whose own type normalizes to a list of objects carrying `v`
     * travels whole inside its entry, in both directions.
     *
     * @throws Exception
     */
    public function testAnEntryHoldingAnEntryShapedValueSurvivesTheRoundTrip(): void
    {
        $table = [['v' => 'cell'], ['v' => 'other']];
        $stored = [];

        $detail = $this->roundTrip($stored, [self::QUALIFIED_KEY => [['v' => $table]]]);

        $this->assertSame($table, $stored[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]);
        $this->assertSame([['v' => $table]], $detail[self::QUALIFIED_GROUP][self::LANGUAGE][self::QUALIFIED_KEY]);
    }

    /**
     * The single default slot needs no capability, so it is written on an engine reporting none, for
     * a client sending a list and for one sending the value itself, and declaring a variant does not
     * make the key uneditable.
     *
     * @throws Exception
     */
    public function testTheSingleDefaultSlotIsWrittenOnAnEngineReportingNoCapability(): void
    {
        $expected = [['80', self::LANGUAGE, null, 0]];

        foreach ([[['v' => '80']], '80'] as $payload) {
            $writtenEntries = [];
            $this->writeAndRecord(
                $this->qualifiedKeyConfigs(['variants' => [self::METRIC, self::IMPERIAL]]),
                [self::QUALIFIED_KEY => $payload],
                capabilities: [],
                writtenEntries: $writtenEntries
            );

            $this->assertCount(1, $writtenEntries);
            $this->assertSame($expected, $this->describeCoreEntries($writtenEntries[0]));
        }
    }

    /**
     * A write needing a capability is refused where the core cannot be asked, and nothing is
     * written; the single default slot stays open.
     *
     * @throws Exception
     */
    public function testAWriteNeedingACapabilityIsRefusedWhereTheCoreCannotBeAsked(): void
    {
        $writtenEntries = [];

        try {
            $this->writeAndRecord(
                $this->qualifiedKeyConfigs(),
                [self::QUALIFIED_KEY => [
                    ['v' => '80', 'var' => self::METRIC],
                    ['v' => '176', 'var' => self::IMPERIAL],
                ]],
                capabilities: null,
                writtenEntries: $writtenEntries
            );
            $this->fail('Expected the write to be refused.');
        } catch (AbstractApiException $exception) {
            $this->assertSame(
                HttpResponseErrorKeys::ENGINE_CAPABILITY_MISSING->value,
                $exception->getErrorKey()
            );
        }

        $this->assertSame([], $writtenEntries);
    }

    /**
     * Core refusing the write because the engine cannot hold what it addresses answers the same 422
     * and the same error key as the capability guard does for the same write, rather than failing
     * the whole object save: the guard reads the capabilities the store reports and core asks the
     * engine's own DAO ({@see Classificationstore::setKeyValues()}), so the two can disagree.
     *
     * @throws Exception
     */
    public function testCoreRefusingTheWriteOnTheEnginesCapabilityAnswersTheGuardsError(): void
    {
        try {
            $this->writeThroughAFailingCore(new UnsupportedEngineFeatureException(
                EngineFeature::Variants,
                self::QUALIFIED_GROUP,
                self::QUALIFIED_KEY,
                'input'
            ));
            $this->fail('Expected the write to be refused.');
        } catch (AbstractApiException $exception) {
            $this->assertSame(HttpResponseErrorKeys::ENGINE_CAPABILITY_MISSING->value, $exception->getErrorKey());
            $this->assertSame(HttpResponseCodes::UNPROCESSABLE_CONTENT->value, $exception->getStatusCode());
        }
    }

    /**
     * Anything else the core write raises is the database failure the sibling read of the same slots
     * answers with, and carries the original exception.
     *
     * @throws Exception
     */
    public function testAnyOtherFailureOfTheCoreWriteIsAnsweredAsADatabaseFailure(): void
    {
        $cause = new Exception('the slot could not be written');

        try {
            $this->writeThroughAFailingCore($cause);
            $this->fail('Expected the write to fail.');
        } catch (DatabaseException $exception) {
            $this->assertSame($cause, $exception->getPrevious());
        }
    }

    /**
     * Writes one entry of a `multiValue` key into a container whose `setKeyValues()` raises the
     * given failure, with every request-boundary check of that write passing first.
     *
     * @throws Exception
     */
    private function writeThroughAFailingCore(Exception $failure): void
    {
        $stored = [];
        $keyConfigs = $this->qualifiedKeyConfigs();
        $container = $this->make(Classificationstore::class, [
            'getObject' => $this->makeEmpty(Concrete::class),
            'getActiveGroups' => [self::QUALIFIED_GROUP => true],
            'getGroupCollectionMappings' => [],
            'getGroupIdsWithData' => [],
            'setActiveGroups' => null,
            'setGroupCollectionMappings' => null,
            'getKeyValues' => [],
            'setKeyValues' => static function () use ($failure): never {
                throw $failure;
            },
        ]);

        $this->createQualifiedAdapter($keyConfigs, $stored, self::BOTH_CAPABILITIES)->getDataForSetter(
            $this->makeEmpty(Concrete::class, ['get' => $container]),
            $this->createQualifiedFieldDefinition($keyConfigs),
            'myStore',
            ['myStore' => [
                self::QUALIFIED_GROUP => [self::LANGUAGE => [
                    self::QUALIFIED_KEY => [['v' => '80', 'var' => self::METRIC]],
                ]],
                'activeGroups' => [self::QUALIFIED_GROUP => true],
            ]],
            $this->makeEmpty(UserInterface::class, ['isAdmin' => true])
        );
    }

    /**
     * The current value the key's own type adapter is handed is the one entry's, not the whole
     * list, matched by qualifier and null where the store holds no value there.
     *
     * @throws Exception
     */
    public function testTheKeysTypeAdapterSeesTheCurrentValueOfTheOneEntryItConverts(): void
    {
        $stored = [self::QUALIFIED_GROUP => [self::LANGUAGE => [self::QUALIFIED_KEY => 'first']]];
        $slots = [self::QUALIFIED_GROUP => [self::QUALIFIED_KEY => [self::LANGUAGE => [
            '' => [1 => 'second', 2 => 'third'],
        ]]]];

        $seen = $this->recordCurrentValuesSeenBySubAdapter(
            $stored,
            [self::QUALIFIED_KEY => [
                ['v' => 'A'],
                ['v' => 'B', 'p' => 1],
                ['v' => 'C', 'p' => 2],
                ['v' => 'D', 'var' => self::IMPERIAL],
            ]],
            $slots
        );

        $this->assertSame(['first', 'second', 'third', null], $seen);
    }

    /**
     * The search preview shows one value: the default variant at position 0, the first value where
     * there is none.
     *
     * @throws Exception
     */
    public function testTheSearchPreviewShowsTheDefaultSlotOfAMultiValueKey(): void
    {
        $keyConfigs = $this->qualifiedKeyConfigs();
        $key = 'Measurements - key-' . self::QUALIFIED_KEY;
        $written = [];
        $writtenEntries = [];

        $withDefault = [self::QUALIFIED_GROUP => [self::LANGUAGE => [self::QUALIFIED_KEY => '80']]];
        $slots = [self::QUALIFIED_GROUP => [self::QUALIFIED_KEY => [self::LANGUAGE => [self::IMPERIAL => [0 => '176']]]]];
        $withoutDefault = [];

        foreach ([[&$withDefault, '80'], [&$withoutDefault, '176']] as [&$stored, $expected]) {
            $data = $this->createQualifiedAdapter($keyConfigs, $stored)->getPreviewFieldData(
                $this->createRecordingContainer(
                    $stored,
                    [self::QUALIFIED_GROUP => true],
                    $written,
                    $writtenEntries,
                    $slots
                ),
                $this->createQualifiedFieldDefinition($keyConfigs),
                []
            );

            $this->assertSame($expected, $data[self::PREVIEW_FIELD][$key]);
        }
    }

    /**
     * @param array<int, MultiValue> $entries
     *
     * @return array<int, array{0: mixed, 1: ?string, 2: ?string, 3: ?int}>
     */
    private function describeCoreEntries(array $entries): array
    {
        return array_map(
            static fn (MultiValue $entry): array => [
                $entry->getValue(),
                $entry->getLanguage(),
                $entry->getVariant(),
                $entry->getPosition(),
            ],
            $entries
        );
    }

    /**
     * @param array<int, KeyConfig> $keyConfigs
     * @param array<int, array<string, array<int, mixed>>> $stored
     * @param array<string, array<int, mixed>> $byLanguage
     *
     * @throws Exception
     */
    private function writeLanguages(
        Classificationstore $container,
        array $keyConfigs,
        array &$stored,
        array $byLanguage,
        bool $isPatch = false
    ): void {
        $this->createQualifiedAdapter(
            $keyConfigs,
            $stored,
            self::BOTH_CAPABILITIES,
            toolResolver: $this->makeEmpty(ToolResolverInterface::class, ['getValidLanguages' => [self::GERMAN]])
        )->getDataForSetter(
            $this->makeEmpty(Concrete::class, ['get' => $container]),
            $this->createQualifiedFieldDefinition($keyConfigs, isLocalized: true),
            'myStore',
            ['myStore' => [
                self::QUALIFIED_GROUP => $byLanguage,
                'activeGroups' => [self::QUALIFIED_GROUP => true],
            ]],
            $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
            isPatch: $isPatch
        );
    }

    /**
     * Writes each request in turn over one container and answers a reader of what core's
     * `getKeyValues()` then holds, by language and variant.
     *
     * @param array<int, array<string, array<int, mixed>>> $requests
     *
     * @throws Exception
     */
    private function writeAndReadLanguages(array $requests): callable
    {
        $stored = [];
        $slots = [];
        $written = [];
        $writtenEntries = [];
        $keyConfigs = $this->qualifiedKeyConfigs();
        $container = $this->createRecordingContainer(
            $stored,
            [self::QUALIFIED_GROUP => true],
            $written,
            $writtenEntries,
            $slots
        );

        foreach ($requests as $byLanguage) {
            $this->writeLanguages($container, $keyConfigs, $stored, $byLanguage);
        }

        return static fn (string $language, ?string $variant): array => $container->getKeyValues(
            self::QUALIFIED_GROUP,
            self::QUALIFIED_KEY,
            $language,
            $variant
        );
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
        $qualified = [self::QUALIFIED_GROUP => [self::LANGUAGE => [self::QUALIFIED_KEY => 'first']]];

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
    private function recordCurrentValuesSeenBySubAdapter(array &$stored, array $keys, array &$slots = []): array
    {
        $seen = [];
        $keyConfigs = $this->qualifiedKeyConfigs();
        $written = [];
        $writtenEntries = [];
        $container = $this->createRecordingContainer(
            $stored,
            [self::QUALIFIED_GROUP => true],
            $written,
            $writtenEntries,
            $slots
        );

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
            self::QUALIFIED_KEY => $this->createKeyConfig($declaration, MultiValueServiceResolver::MULTI_VALUE_TYPE),
            self::PLAIN_KEY => $this->createKeyConfig(['fieldtype' => 'input']),
        ];
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function createKeyConfig(array $definition, string $type = 'input'): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setName('weight');
        $keyConfig->setType($type);
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
        ?ServiceResolverInterface $serviceResolver = null,
        array &$writtenEntries = []
    ): array {
        $container = $this->createRecordingContainer(
            $stored,
            [self::QUALIFIED_GROUP => true],
            $written,
            $writtenEntries
        );
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
     * go in on setLocalizedKeyValue and setKeyValues and come back out on getLocalizedKeyValue and
     * getKeyValues, so a read asserts what the write actually left behind.
     *
     * `setKeyValues()` is core's, down to what a write replaces: it "replaces the (language,
     * variant) slots the given entries name, and leaves every other slot of the key as it is:
     * writing one variant does not touch the values of another"
     * ({@see Classificationstore::setKeyValues()}), the slot of a key declaring a list being
     * replaced whole, so that a write dropping a value drops it (`Classificationstore::storeSlot()`).
     * Position 0 of the default variant is the value `$stored` holds at (group, language, key),
     * which is where core keeps it too.
     *
     * @param array<int, array<string, array<int, mixed>>> $stored
     * @param array<int, bool> $activeGroups
     * @param array<int, array<int, mixed>> $written
     * @param array<int, array<int, MultiValue[]>> $writtenEntries one list per setKeyValues call
     *
     * @throws Exception
     */
    private function createRecordingContainer(
        array &$stored,
        array $activeGroups,
        array &$written = [],
        array &$writtenEntries = [],
        array &$slots = []
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
            'setKeyValues' => function (
                int $groupId,
                int $keyId,
                array $entries
            ) use (&$stored, &$slots, &$writtenEntries, &$container) {
                $writtenEntries[] = $entries;

                $replaced = [];
                foreach ($entries as $entry) {
                    $language = $entry->getLanguage() ?? 'default';
                    $variant = $entry->getVariant() ?? '';
                    $replaced[$language][$variant][$entry->getPosition() ?? 0] = $entry->getValue();
                }

                foreach ($replaced as $language => $variants) {
                    foreach ($variants as $variant => $values) {
                        $values = array_filter($values, static fn (mixed $value): bool => $value !== null);
                        ksort($values);
                        $slots[$groupId][$keyId][$language][$variant] = $values;

                        if ($variant === '') {
                            $stored[$groupId][$language][$keyId] = $values[0] ?? null;
                        }
                    }
                }

                return $container;
            },
            'getKeyValues' => static function (
                int $groupId,
                int $keyId,
                ?string $language = null,
                ?string $variant = null
            ) use (&$stored, &$slots): array {
                $language ??= 'default';
                $values = $slots[$groupId][$keyId][$language][$variant ?? ''] ?? [];

                if ($variant === null && ($stored[$groupId][$language][$keyId] ?? null) !== null) {
                    $values[0] = $stored[$groupId][$language][$keyId];
                    ksort($values);
                }

                return $values;
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
        ?DataServiceInterface $dataService = null,
        ?ToolResolverInterface $toolResolver = null
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
            toolResolver: $toolResolver,
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
        $keyQualifierResolver = new KeyQualifierResolver(new MultiValueServiceResolver());

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
