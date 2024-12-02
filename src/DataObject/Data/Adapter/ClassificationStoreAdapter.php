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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateFieldTypeTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore as ClassificationstoreModel;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing as KeyGroupRelationListing;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function in_array;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ClassificationStoreAdapter implements SetterDataInterface, DataNormalizerInterface
{
    use ValidateFieldTypeTrait;

    public function __construct(
        private DefinitionCacheResolverInterface $definitionCacheResolver,
        private DataAdapterServiceInterface $dataAdapterService,
        private DataServiceInterface $dataService,
        private GroupConfigResolverInterface $groupConfigResolver,
        private ServiceResolverInterface $serviceResolver,
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
        ?FieldContextData $contextData = null
    ): ?Classificationstore {

        if (!$fieldDefinition instanceof ClassificationstoreDefinition) {
            return null;
        }

        $store = $data[$key];
        $container = $this->getContainer($element, $key, $contextData);
        $this->setMapping($container, $store);
        if (is_array($store['data'])) {
            $this->setStoreValues($element, $fieldDefinition, $container, $store);
        }
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

        $validLanguages = $this->getValidLanguages($fieldDefinition);
        $resultItems = [];

        foreach ($this->getActiveGroups($value) as $groupId => $groupConfig) {
            $resultItems[$groupConfig->getName()] = [];
            $keys = $this->getClassificationStoreKeysFromGroup($groupConfig);
            foreach ($validLanguages as $validLanguage) {
                foreach ($keys as $key) {
                    $normalizedValue = $this->getNormalizedValue($value, $groupId, $key, $validLanguage);

                    if ($normalizedValue !== null) {
                        $resultItems[$groupConfig->getName()][$validLanguage][$key->getName()] = $normalizedValue;
                    }
                }
            }
        }

        return $resultItems;
    }

    /**
     * @throws Exception
     */
    private function getContainer(
        Concrete $element,
        string $key,
        ?FieldContextData $contextData
    ): Classificationstore {
        $container = $element->get($key, $contextData?->getLanguage());

        if (!$container instanceof Classificationstore) {
            return new Classificationstore();
        }

        return $container;
    }

    private function setMapping(Classificationstore $container, array $data): void
    {
        $activeGroups = $data['activeGroups'];
        $groupCollectionMapping = $data['groupCollectionMapping'];

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
        ClassificationstoreDefinition $definition,
        Classificationstore $container,
        array $store
    ): void {
        $activeGroups = $store['activeGroups'];
        foreach ($store['data'] as $language => $groups) {
            foreach ($groups as $groupId => $keys) {
                $this->processGroupKeys($element, $definition, $container, $language, $groupId, $keys);
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
        ClassificationstoreDefinition $definition,
        Classificationstore $container,
        string $language,
        int $groupId,
        array $keys
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
                [$fieldDefinition->getName() => $value]
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

    private function getValidLanguages(ClassificationstoreDefinition $classificationStore): array
    {
        $languages = [MappingProperty::NOT_LOCALIZED_KEY];
        if ($classificationStore->isLocalized()) {
            $languages = array_merge($languages, $this->toolResolver->getValidLanguages());
        }

        return $languages;
    }

    /**
     * @return GroupConfig[]
     */
    private function getActiveGroups(ClassificationstoreModel $value): array
    {
        $groups = [];
        foreach ($value->getActiveGroups() as $groupId => $active) {
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
    private function getClassificationStoreKeysFromGroup(GroupConfig $groupConfig): array
    {
        $listing = new KeyGroupRelationListing();
        $listing->addConditionParam('groupId = ?', $groupConfig->getId());

        return $listing->getList();
    }

    private function getNormalizedValue(
        ClassificationstoreModel $classificationstore,
        int $groupId,
        KeyGroupRelation $key,
        string $language
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

        return $this->dataService->getNormalizedValue($value, $fieldDefinition);
    }
}
