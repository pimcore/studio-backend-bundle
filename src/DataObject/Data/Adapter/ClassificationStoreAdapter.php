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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function in_array;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ClassificationStoreAdapter implements SetterDataInterface
{
    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService,
        private ServiceResolverInterface $serviceResolver
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
        $correctedMapping = [];

        foreach ($groupCollectionMapping as $groupId => $collectionId) {
            if (isset($activeGroups[$groupId]) && $activeGroups[$groupId]) {
                $correctedMapping[$groupId] = $collectionId;
            }
        }

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

            $adapter = $this->dataAdapterService->getDataAdapter($fieldDefinition->getFieldType());
            $setterData = $adapter->getDataForSetter(
                $element,
                $fieldDefinition,
                $fieldDefinition->getName(),
                [$fieldDefinition->getName() => $value]
            );

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
}
