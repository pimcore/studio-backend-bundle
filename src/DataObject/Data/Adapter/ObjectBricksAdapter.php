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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataInheritanceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SearchPreviewDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ObjectBricksAdapter implements
    SetterDataInterface,
    DataNormalizerInterface,
    DataInheritanceInterface,
    SearchPreviewDataInterface
{
    use ValidateObjectDataTrait;

    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService,
        private DataServiceInterface $dataService,
        private DefinitionResolverInterface $definitionResolver,
        private InheritanceServiceInterface $inheritanceService,
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
    ): ?Objectbrick {
        if (!$fieldDefinition instanceof Objectbricks) {
            return null;
        }
        $brickData = $data[$key];
        $container = $this->getContainer($element, $key, $fieldDefinition->getName(), $contextData);
        $brickKeys = array_keys($brickData);

        foreach ($brickKeys as $brickKey) {
            $brick = $this->getBrick($element, $container, $brickKey);
            $brick->setFieldname($fieldDefinition->getName());
            $brickValue = $brickData[$brickKey];

            if ($this->checkDeleteBrick($brick, $brickValue)) {
                continue;
            }

            $this->processBrickData($element, $user, $container, $brick, $brickValue, $isPatch);
        }

        return $container;
    }

    public function normalize(
        mixed $value,
        Data $fieldDefinition
    ): ?array {
        if (!$value instanceof Objectbrick) {
            return null;
        }

        $resultItems = [];
        $allowedTypes = $value->getAllowedBrickTypes();
        foreach ($allowedTypes as $type) {
            $resultItems[$type] = null;
            $item = $value->get($type);

            if (!$item instanceof AbstractData) {
                continue;
            }

            $definition = $this->definitionResolver->getByKey($type);
            if ($definition === null) {
                continue;
            }

            foreach ($definition->getFieldDefinitions() as $brickFieldDefinition) {
                $fieldName = $brickFieldDefinition->getName();
                $resultItems[$type][$fieldName] = $this->dataService->getNormalizedValue(
                    $item->get($fieldName),
                    $brickFieldDefinition,
                );
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
        $value = $this->getValidFieldValue($object, $key);
        if (!$value instanceof Objectbrick) {
            return [];
        }

        $inheritanceData = [];
        foreach ($value->getAllowedBrickTypes() as $type) {
            $brick = $value->get($type);
            if (!$brick) {
                continue;
            }

            $inheritanceData[$type] = [];
            $brickDefinition = $this->definitionResolver->getByKey($type);
            if ($brickDefinition === null) {
                continue;
            }

            $contextData = new FieldContextData($brick, $contextData?->getLanguage());
            foreach ($brickDefinition->getFieldDefinitions() as $definition) {
                $fieldName = $definition->getName();
                $inheritanceData[$type][$fieldName] = $this->inheritanceService->processFieldDefinition(
                    $object,
                    $definition,
                    $fieldName,
                    $contextData
                );
            }
        }

        return $inheritanceData;
    }

    public function getPreviewFieldData(
        mixed $value,
        Data $fieldDefinition,
        array $data
    ): array {
        if (!$value instanceof Objectbrick) {
            return $data;
        }

        $allowedTypes = $value->getAllowedBrickTypes();
        foreach ($allowedTypes as $type) {
            $item = $value->get($type);
            if (!$item instanceof AbstractData) {
                $data[$type] = null;

                continue;
            }

            $definition = $this->definitionResolver->getByKey($type);
            if ($definition === null) {
                $data[$type] = null;

                continue;
            }

            foreach ($definition->getFieldDefinitions() as $brickFieldDefinition) {
                $fieldValues = $this->dataService->getPreviewFieldData(
                    $item->get($brickFieldDefinition->getName()),
                    $brickFieldDefinition,
                    []
                );
                foreach ($fieldValues as $fieldKey => $fieldValue) {
                    $data[ucfirst($type) . ' - ' . $fieldKey] = $fieldValue;
                }

            }
        }

        return $data;
    }

    /**
     * @throws Exception
     */
    private function getContainer(
        Concrete $element,
        string $key,
        string $fieldName,
        ?FieldContextData $contextData
    ): Objectbrick {
        $container = $element->get($key, $contextData?->getLanguage());

        if ($container instanceof Objectbrick) {
            return $container;
        }

        $className = $element->getClass()->getName();
        $containerClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className) . '\\' . ucfirst($fieldName);

        return new $containerClass($element, $fieldName);
    }

    private function getBrick(Concrete $element, Objectbrick $container, string $brickType): AbstractData
    {
        $brick = $container->get($brickType);
        if ($brick instanceof AbstractData) {
            return $brick;
        }

        $brickClass = '\\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickType);

        return new $brickClass($element);
    }

    /**
     * @throws Exception
     */
    private function processBrickData(
        Concrete $element,
        UserInterface $user,
        Objectbrick $container,
        AbstractData $brick,
        array $brickValue,
        bool $isPatch
    ): void {

        $brickKey = $brick->getType();
        $collectionDef = $this->definitionResolver->getByKey($brickKey);
        if ($collectionDef === null) {
            return;
        }

        $brick->setValues($this->getCollectionData(
            $collectionDef,
            $brickValue,
            $element,
            $user,
            $brick,
            $isPatch
        ));
        $container->set($brickKey, $brick);
    }

    private function getCollectionData(
        Definition $collectionDef,
        array $brickValue,
        Concrete $element,
        UserInterface $user,
        AbstractData $brick,
        bool $isPatch
    ): array {
        $collectionData = [];

        foreach ($collectionDef->getFieldDefinitions() as $fd) {
            $adapter = $this->dataAdapterService->tryDataAdapter($fd->getFieldType());
            $fieldName = $fd->getName();
            if (!$adapter || !array_key_exists($fieldName, $brickValue)) {
                continue;
            }

            $value = $adapter->getDataForSetter(
                $element,
                $fd,
                $fieldName,
                [$fieldName => $brickValue[$fieldName]],
                $user,
                new FieldContextData($brick),
                $isPatch
            );
            if (!$this->validateEncryptedField($fd, $value)) {
                continue;
            }

            $collectionData[$fieldName] = $value;
        }

        return $collectionData;
    }

    private function checkDeleteBrick(AbstractData $brick, array $brickValue): bool
    {
        $action = $brickValue['action'] ?? null;
        if ($action === 'deleted') {
            $brick->setDoDelete(true);

            return true;
        }

        return false;
    }
}
