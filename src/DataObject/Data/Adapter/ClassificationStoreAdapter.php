<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\MappingProperty;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\DefinitionCacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\GroupConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataInheritanceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SearchPreviewDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore as ClassificationstoreModel;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing as KeyGroupRelationListing;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function in_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ClassificationStoreAdapter implements
    SetterDataInterface,
    DataNormalizerInterface,
    DataInheritanceInterface,
    SearchPreviewDataInterface
{
    use ValidateObjectDataTrait;

    public function __construct(
        private DefinitionCacheResolverInterface $definitionCacheResolver,
        private DataAdapterServiceInterface $dataAdapterService,
        private DataServiceInterface $dataService,
        private GroupConfigResolverInterface $groupConfigResolver,
        private InheritanceServiceInterface $inheritanceService,
        private LanguageServiceInterface $languageService,
        private ServiceResolverInterface $serviceResolver,
        private SecurityServiceInterface $securityService,
        private ToolResolverInterface $toolResolver
    ) {
    }

    /**
     * @throws Exception
     */
    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?Classificationstore {

        if (!$fieldDefinition instanceof ClassificationstoreDefinition) {
            return null;
        }

        $store = $data[$key];
        $activeGroups = $store['activeGroups'] ?? [];
        if (empty($activeGroups)) {
            return null;
        }
        $groupCollectionMapping = $store['groupCollectionMapping'] ?? [];
        $container = $this->getContainer($element, $key, $contextData);
        if (!empty($groupCollectionMapping)) {
            $this->setMapping($container, $store['activeGroups'], $store['groupCollectionMapping']);
        }
        unset($store['activeGroups'], $store['groupCollectionMapping']);
        $this->setStoreValues($element, $user, $fieldDefinition, $container, $store, $isPatch);
        $this->cleanupStoreGroups($container);

        return $container;
    }

    public function normalize(
        mixed $value,
        Data $fieldDefinition
    ): ?array {
        if (!$value instanceof ClassificationstoreModel ||
            !$fieldDefinition instanceof ClassificationstoreDefinition
        ) {
            return null;
        }

        $validLanguages = $this->getValidLanguages($value->getObject(), $fieldDefinition->isLocalized());
        $resultItems = [];

        $resultItems['activeGroups'] = $value->getActiveGroups();
        $resultItems['groupCollectionMapping'] = $value->getGroupCollectionMappings();

        foreach ($this->getActiveGroupsConfig($resultItems['activeGroups']) as $groupId => $groupConfig) {
            $resultItems[$groupId] = [];
            $keys = $this->getClassificationStoreKeysFromGroup($groupId);
            foreach ($validLanguages as $validLanguage) {
                foreach ($keys as $key) {
                    $normalizedValue = $this->getNormalizedValue($value, $groupId, $key, $validLanguage);

                    if ($normalizedValue !== null) {
                        $resultItems[$groupId][$validLanguage][$key->getKeyId()] = $normalizedValue;
                    }
                }
            }
        }

        return $resultItems;
    }

    public function getFieldInheritance(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): array {
        $inheritedData = [];
        if (!$fieldDefinition instanceof ClassificationstoreDefinition) {
            return [];
        }
        $languages = $this->getValidLanguages($object, $fieldDefinition->isLocalized());
        $collection = $this->getStoreDefinitions($object, $fieldDefinition);
        if (empty($collection)) {
            $originId = $this->inheritanceService->getOriginId($object, $fieldDefinition, $key, $contextData);

            return [new InheritanceData($originId, $originId !== $object->getId())];
        }

        $container = $this->getContainer($object, $key, $contextData);
        foreach ($collection as $groupId => $groupDefinitions) {
            foreach ($groupDefinitions as $groupKeyId => $definition) {
                foreach ($languages as $language) {
                    $originId = $this->inheritanceService->getOriginId(
                        $object,
                        $definition,
                        $key,
                        new FieldContextData($container, $language, $groupId, $groupKeyId)
                    );

                    $inheritedData[$groupId][$language][$groupKeyId] = new InheritanceData(
                        $originId,
                        $originId !== $object->getId()
                    );
                }
            }
        }

        return $inheritedData;
    }

    public function getPreviewFieldData(
        mixed $value,
        Data $fieldDefinition,
        array $data
    ): array {
        if (!$value instanceof ClassificationstoreModel ||
            !$fieldDefinition instanceof ClassificationstoreDefinition
        ) {
            return $data;
        }

        $fieldName =$this->dataService->getPreviewFieldName($fieldDefinition);
        $validLanguages = $this->getValidLanguages($value->getObject(), $fieldDefinition->isLocalized());
        foreach ($this->getActiveGroupsConfig($value->getActiveGroups()) as $groupId => $groupConfig) {
            $keys = $this->getClassificationStoreKeysFromGroup($groupId);
            $groupName = $groupConfig->getName();
            foreach ($keys as $key) {
                foreach ($validLanguages as $validLanguage) {
                    $previewValue = $this->getPreviewValue($value, $groupId, $key, $validLanguage, $data);
                    $previewKey = $groupName . ' - ' . $key->getName() .
                        ($fieldDefinition->isLocalized() ? ' / ' . $validLanguage : null);
                    $data[$fieldName][$previewKey] = $previewValue;
                }
            }
        }

        return $data;
    }

    /**
     * @throws NotFoundException
     */
    private function getContainer(
        Concrete $element,
        string $key,
        ?FieldContextData $contextData
    ): Classificationstore {
        $container = $this->getValidFieldValue($element, $key, $contextData);

        if (!$container instanceof Classificationstore) {
            return new Classificationstore();
        }

        return $container;
    }

    private function setMapping(
        Classificationstore $container,
        array $activeGroups,
        array $groupCollectionMapping
    ): void {
        $correctedMapping = array_filter($groupCollectionMapping, static function ($groupId) use ($activeGroups) {
            return isset($activeGroups[$groupId]) && $activeGroups[$groupId];
        }, ARRAY_FILTER_USE_KEY);

        $container->setGroupCollectionMappings($correctedMapping);
    }

    /**
     * @throws Exception
     */
    private function setStoreValues(
        Concrete $element,
        UserInterface $user,
        ClassificationstoreDefinition $definition,
        Classificationstore $container,
        array $store,
        bool $isPatch
    ): void {

        $activeGroups = [];

        foreach ($store as $groupId => $groupData) {
            foreach ($groupData as $language => $keys) {
                if (!in_array(
                    $language,
                    $this->getValidLanguages(
                        $element,
                        $definition->isLocalized(),
                        ElementPermissions::LANGUAGE_EDIT_PERMISSIONS,
                        $user
                    ),
                    true
                )) {
                    continue;
                }
                $this->processGroupKeys($element, $user, $definition, $container, $language, $groupId, $keys, $isPatch);
                $activeGroups[$groupId] = true;
            }
        }

        $container->setActiveGroups($activeGroups);
    }

    /**
     * @throws Exception
     */
    private function processGroupKeys(
        Concrete $element,
        UserInterface $user,
        ClassificationstoreDefinition $definition,
        Classificationstore $container,
        string $language,
        int $groupId,
        array $keys,
        bool $isPatch
    ): void {
        foreach ($keys as $keyId => $value) {
            $fieldDefinition = $this->serviceResolver->getFieldDefinitionFromKeyConfig(
                $definition->getKeyConfiguration($keyId)
            );

            if ($fieldDefinition === null) {
                continue;
            }

            $adapter = $this->dataAdapterService->tryDataAdapter($fieldDefinition->getFieldType());
            if ($adapter === null) {
                continue;
            }

            $setterData = $adapter->getDataForSetter(
                $element,
                $fieldDefinition,
                $fieldDefinition->getName(),
                [$fieldDefinition->getName() => $value],
                $user,
                new FieldContextData($container, $language, $groupId, $keyId),
                $isPatch
            );
            if (!$this->validateEncryptedField($fieldDefinition, $setterData)) {
                continue;
            }

            $container->setLocalizedKeyValue($groupId, $keyId, $setterData, $language);
        }
    }

    private function cleanupStoreGroups(Classificationstore $container): void
    {
        $activeGroupIds = array_keys($container->getActiveGroups());
        $existingGroupIds = $container->getGroupIdsWithData();

        foreach ($existingGroupIds as $existingGroupId) {
            if (!in_array($existingGroupId, $activeGroupIds, true)) {
                $container->removeGroupData($existingGroupId);
            }
        }
    }

    private function getStoreDefinitions(
        Concrete $dataObject,
        ClassificationstoreDefinition $classificationStore
    ): array {
        $mapping = [];
        foreach ($classificationStore->recursiveGetActiveGroupsIds($dataObject) as $groupId => $active) {
            if (!$active) {
                continue;
            }

            $keys = $this->getClassificationStoreKeysFromGroup($groupId);
            foreach ($keys as $groupKey) {
                $definition = $this->serviceResolver->getFieldDefinitionFromKeyConfig($groupKey);
                if ($definition === null) {
                    continue;
                }
                $mapping[$groupKey->getGroupId()][$groupKey->getKeyId()] = $definition;
            }
        }

        return $mapping;
    }

    private function getValidLanguages(
        Concrete $object,
        bool $isStoreLocalized,
        string $permissionType = ElementPermissions::LANGUAGE_VIEW_PERMISSIONS,
        ?UserInterface $user = null
    ): array {
        $languages = [MappingProperty::NOT_LOCALIZED_KEY];
        if ($isStoreLocalized === false) {
            return $languages;
        }

        if ($user === null) {
            $user = $this->securityService->getCurrentUser();
        }

        if ($user->isAdmin()) {
            return array_merge($languages, $this->toolResolver->getValidLanguages());
        }

        return $this->languageService->getUserAllowedLanguages(
            $object,
            $user,
            $permissionType
        );
    }

    /**
     * @return GroupConfig[]
     */
    private function getActiveGroupsConfig(array $activeGroups): array
    {
        $groups = [];
        foreach ($activeGroups as $groupId => $active) {
            if ($active) {
                $groupConfig = $this->groupConfigResolver->getById($groupId);
                if ($groupConfig) {
                    $groups[$groupId] = $groupConfig;
                }
            }
        }

        return $groups;
    }

    /**
     * @return KeyGroupRelation[]
     */
    private function getClassificationStoreKeysFromGroup(int $groupId): array
    {
        $listing = new KeyGroupRelationListing();
        $listing->addConditionParam('groupId = ?', $groupId);

        return $listing->getList();
    }

    /**
     * @throws DatabaseException
     */
    private function getNormalizedValue(
        ClassificationstoreModel $classificationstore,
        int $groupId,
        KeyGroupRelation $key,
        string $language
    ): mixed {
        return $this->getValue($classificationstore, $groupId, $key, $language);
    }

    /**
     * @throws DatabaseException
     */
    private function getPreviewValue(
        ClassificationstoreModel $classificationstore,
        int $groupId,
        KeyGroupRelation $key,
        string $language,
        array $data
    ): mixed {
        return $this->getValue($classificationstore, $groupId, $key, $language, $data, true);
    }

    /**
     * Retrieves normalized or preview values for classification store keys.
     *
     * @throws DatabaseException
     */
    private function getValue(
        ClassificationstoreModel $classificationstore,
        int $groupId,
        KeyGroupRelation $key,
        string $language,
        ?array $data = null,
        bool $isPreview = false
    ): mixed {
        try {
            $value = $classificationstore->getLocalizedKeyValue(
                $groupId,
                $key->getKeyId(),
                $language,
                true,
                true
            );
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage());
        }

        $keyConfig = $this->definitionCacheResolver->get($key->getKeyId());
        if ($keyConfig === null) {
            return null;
        }

        $fieldDefinition = $this->serviceResolver->getFieldDefinitionFromKeyConfig($keyConfig);
        if ($fieldDefinition === null) {
            return null;
        }

        if ($isPreview && $data !== null) {
            $data = $this->dataService->getPreviewFieldData($value, $fieldDefinition, $data);

            return $data[$this->dataService->getPreviewFieldName($fieldDefinition)];
        }

        return $this->dataService->getNormalizedValue($value, $fieldDefinition);
    }
}
