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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\ClassificationStoreAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use function array_keys;
use function array_merge;
use function sprintf;

/**
 * @internal
 */
final class ClassificationStoreAdapterTest extends Unit
{
    private const string PREVIEW_FIELD = 'previewField';

    private const array GROUP_NAMES = [10 => 'Colour', 20 => 'Size'];

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
    ): ClassificationStoreAdapter {
        return new ClassificationStoreAdapter(
            $dataObjectServiceResolver ?? $this->makeEmpty(DataObjectServiceResolverInterface::class),
            $definitionCacheResolver ?? $this->makeEmpty(DefinitionCacheResolverInterface::class),
            $dataAdapterService ?? $this->makeEmpty(DataAdapterServiceInterface::class),
            $dataService ?? $this->makeEmpty(DataServiceInterface::class),
            $groupConfigResolver ?? $this->makeEmpty(GroupConfigResolverInterface::class),
            $inheritanceService ?? $this->makeEmpty(InheritanceServiceInterface::class),
            $keyRelationResolver ?? $this->makeEmpty(KeyRelationResolverInterface::class),
            $languageService ?? $this->makeEmpty(LanguageServiceInterface::class),
            $serviceResolver ?? $this->makeEmpty(ServiceResolverInterface::class),
            $securityService ?? $this->makeEmpty(SecurityServiceInterface::class),
            $toolResolver ?? $this->makeEmpty(ToolResolverInterface::class),
        );
    }
}
