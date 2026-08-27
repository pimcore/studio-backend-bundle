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
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ClassificationStoreAdapterTest extends Unit
{
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
        $storedLanguages = $this->getStoredLanguagesForNonAdmin(isLanguageIndependentValueAllowed: true);

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
        $storedLanguages = $this->getStoredLanguagesForNonAdmin(isLanguageIndependentValueAllowed: false);

        $this->assertNotContains('default', $storedLanguages);
        $this->assertContains('de', $storedLanguages);
    }

    /**
     * @return array<int, string|null>
     *
     * @throws Exception
     */
    private function getStoredLanguagesForNonAdmin(bool $isLanguageIndependentValueAllowed): array
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
                'getUserAllowedLanguages' => ['de', 'en'],
                'isLanguageIndependentValueAllowed' => $isLanguageIndependentValueAllowed,
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

    private function createAdapter(
        ?DataObjectServiceResolverInterface $dataObjectServiceResolver = null,
        ?DefinitionCacheResolverInterface $definitionCacheResolver = null,
        ?DataAdapterServiceInterface $dataAdapterService = null,
        ?DataServiceInterface $dataService = null,
        ?GroupConfigResolverInterface $groupConfigResolver = null,
        ?InheritanceServiceInterface $inheritanceService = null,
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
            $languageService ?? $this->makeEmpty(LanguageServiceInterface::class),
            $serviceResolver ?? $this->makeEmpty(ServiceResolverInterface::class),
            $securityService ?? $this->makeEmpty(SecurityServiceInterface::class),
            $toolResolver ?? $this->makeEmpty(ToolResolverInterface::class),
        );
    }
}
