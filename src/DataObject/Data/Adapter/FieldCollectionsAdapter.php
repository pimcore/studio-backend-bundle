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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\FieldCollection\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\Factory;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class FieldCollectionsAdapter implements SetterDataInterface, DataNormalizerInterface
{
    use ValidateObjectDataTrait;

    private const string TYPE_KEY = 'type';

    private const string DATA_KEY = 'data';

    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService,
        private DataServiceInterface $dataService,
        private DefinitionResolverInterface $fieldCollectionDefinition,
        private Factory $modelFactory
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
    ): ?Fieldcollection {
        if (!$fieldDefinition instanceof Fieldcollections) {
            return null;
        }

        $fcData = $data[$key];
        $values = [];
        $count = 0;

        foreach ($fcData as $index => $collectionRaw) {
            $collectionData = $this->processCollectionRaw(
                $element,
                $user,
                $fieldDefinition,
                $collectionRaw,
                $isPatch,
                $this->createFieldContextData($element, $fieldDefinition, $index, $contextData),
            );

            $collection = $this->createCollection(
                $element,
                $fieldDefinition,
                $collectionRaw[self::TYPE_KEY],
                $collectionData,
                $count
            );
            $values[] = $collection;
            $count++;
        }

        return new Fieldcollection($values, $fieldDefinition->getName());
    }

    public function normalize(
        mixed $value,
        Data $fieldDefinition
    ): ?array {
        if (!$value instanceof Fieldcollection) {
            return null;
        }

        $resultItems = [];
        $items = $value->getItems();

        foreach ($items as $item) {
            $type = $item->getType();
            $fieldCollectionDefinition = $this->fieldCollectionDefinition->getByKey($item->getType());
            if (!$fieldCollectionDefinition) {
                continue;
            }
            $resultItem = [self::TYPE_KEY => $type, self::DATA_KEY => []];

            foreach ($fieldCollectionDefinition->getFieldDefinitions() as $collectionFieldDefinition) {
                $getter = 'get' . ucfirst($collectionFieldDefinition->getName());
                $value = $item->$getter();
                $resultItem[self::DATA_KEY][$collectionFieldDefinition->getName()] =
                    $this->dataService->getNormalizedValue($value, $collectionFieldDefinition);
            }

            $resultItems[] = $resultItem;
        }

        return $resultItems;
    }

    /**
     * @throws Exception
     */
    private function createFieldContextData(
        Concrete $element,
        Data $fieldDefinition,
        int $index,
        ?FieldContextData $contextData = null,
    ): FieldContextData {
        $object = $contextData?->getContextObject() ?? $element;

        return new FieldContextData(
            $object->get($fieldDefinition->getName())?->get($index),
            $contextData?->getLanguage()
        );
    }

    /**
     * @throws Exception
     */
    private function processCollectionRaw(
        Concrete $element,
        UserInterface $user,
        Data $fieldDefinition,
        array $collectionRaw,
        bool $isPatch,
        ?FieldContextData $contextData
    ): array {
        $collectionData = [];
        $blockElement = $collectionRaw['data'] ?? null;
        $collectionDef = Fieldcollection\Definition::getByKey($collectionRaw['type']);

        foreach ($collectionDef?->getFieldDefinitions() as $elementName => $fd) {
            $elementValue = $blockElement[$elementName] ?? null;
            $adapter = $this->dataAdapterService->tryDataAdapter($fd->getFieldType());
            if (!$elementValue || !$adapter) {
                continue;
            }

            $value = $adapter->getDataForSetter(
                $element,
                $fd,
                $elementName,
                [$elementName => $elementValue],
                $user,
                $contextData,
                $isPatch,
            );
            if (!$this->validateEncryptedField($fieldDefinition, $value)) {
                continue;
            }

            $collectionData[$elementName] = $value;
        }

        return $collectionData;
    }

    private function createCollection(
        Concrete $element,
        Data $fieldDefinition,
        string $collectionType,
        array $collectionData,
        int $index
    ): AbstractData {
        $collectionClass = '\\Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\' . ucfirst($collectionType);
        /** @var AbstractData $collection */
        $collection = $this->modelFactory->build($collectionClass);
        $collection->setObject($element);
        $collection->setIndex($index);
        $collection->setFieldname($fieldDefinition->getName());
        $collection->setValues($collectionData);

        return $collection;
    }
}
