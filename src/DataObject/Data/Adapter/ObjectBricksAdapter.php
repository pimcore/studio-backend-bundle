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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataInheritanceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateFieldTypeTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ObjectBricksAdapter implements
    SetterDataInterface,
    DataNormalizerInterface,
    DataInheritanceInterface
{
    use ValidateFieldTypeTrait;

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
        ?FieldContextData $contextData = null
    ): ?Objectbrick {
        if (!$fieldDefinition instanceof Objectbricks) {
            return null;
        }
        $brickData = $data[$key];
        $container = $this->getContainer($element, $key, $fieldDefinition->getName(), $contextData);

        foreach ($brickData as $collectionRaw) {
            $this->processBrickData($element, $container, $fieldDefinition, $collectionRaw);
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
        $items = $value->getObjectVars();
        foreach ($items as $item) {
            if (!$item instanceof AbstractData) {
                continue;
            }

            $type = $item->getType();
            $resultItems[$type] = [];
            $definition = $this->definitionResolver->getByKey($type);
            if ($definition === null) {
                continue;
            }

            foreach ($definition->getFieldDefinitions() as $brickFieldDefinition) {
                $getter = 'get' . ucfirst($brickFieldDefinition->getName());
                $value = $item->$getter();
                $resultItems[$type][$brickFieldDefinition->getName()] = $this->dataService->getNormalizedValue(
                    $value,
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
            $brickGetter = 'get' . $type;
            $brick = $value->$brickGetter();
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
                $inheritanceData[$type][$definition->getName()] = $this->inheritanceService->processFieldDefinition(
                    $object,
                    $definition,
                    $key,
                    $contextData
                );
            }
        }

        return $inheritanceData;
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
        Objectbrick $container,
        Data $fieldDefinition,
        array $collectionRaw
    ): void {
        $collectionDef = $this->definitionResolver->getByKey($collectionRaw['type']);
        if ($collectionDef === null) {
            return;
        }

        $brick = $this->getBrick($element, $container, $collectionRaw['type']);
        $brick->setFieldname($fieldDefinition->getName());
        if ($collectionRaw['data'] === 'deleted') {
            $brick->setDoDelete(true);

            return;
        }

        $brick->setValues($this->getCollectionData(
            $collectionDef,
            $collectionRaw['data'],
            $element,
            $brick,
        ));
        $container->set($collectionRaw['type'], $brick);
    }

    private function getCollectionData(
        Definition $collectionDef,
        array $rawData,
        Concrete $element,
        AbstractData $brick
    ): array {
        $collectionData = [];
        foreach ($collectionDef->getFieldDefinitions() as $fd) {
            $adapter = $this->dataAdapterService->tryDataAdapter($fd->getFieldType());
            $fieldName = $fd->getName();
            if (!array_key_exists($fieldName, $rawData) || !$adapter) {
                continue;
            }

            $value = $adapter->getDataForSetter(
                $element,
                $fd,
                $fieldName,
                [$fieldName => $rawData[$fieldName]],
                new FieldContextData($brick)
            );
            if (!$this->validateEncryptedField($fd, $value)) {
                continue;
            }

            $collectionData[$fieldName] = $value;
        }

        return $collectionData;
    }
}
