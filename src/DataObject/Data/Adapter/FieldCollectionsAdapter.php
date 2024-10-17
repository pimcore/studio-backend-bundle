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
use Pimcore;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\Factory;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class FieldCollectionsAdapter implements SetterDataInterface
{
    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService,
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
        ?FieldContextData $contextData = null
    ): ?Fieldcollection {
        if (!$fieldDefinition instanceof Fieldcollections) {
            return null;
        }

        $fcData = $data[$key];
        $values = [];
        $count = 0;

        foreach ($fcData as $collectionRaw) {
            $collectionData = $this->processCollectionRaw($element, $fieldDefinition, $collectionRaw, $contextData);
            $collection = $this->createCollection(
                $element,
                $fieldDefinition,
                $collectionRaw['type'],
                $collectionData,
                $count
            );
            $values[] = $collection;
            $count++;
        }

        return new Fieldcollection($values, $fieldDefinition->getName());
    }

    /**
     * @throws Exception
     */
    private function createFieldContextData(
        Concrete $element,
        Data $fieldDefinition,
        ?FieldContextData $contextData = null
    ): FieldContextData {
        $object = $contextData?->getContextObject() ?? $element;

        return new FieldContextData(
            $object->get($fieldDefinition->getName()),
            $contextData?->getLanguage()
        );
    }

    /**
     * @throws Exception
     */
    private function processCollectionRaw(
        Concrete $element,
        Data $fieldDefinition,
        array $collectionRaw,
        ?FieldContextData $contextData
    ): array {
        $collectionData = [];
        $blockElement = $collectionRaw['data'] ?? null;
        $collectionDef = Fieldcollection\Definition::getByKey($collectionRaw['type']);
        $fieldContextData = $this->createFieldContextData($element, $fieldDefinition, $contextData);

        foreach ($collectionDef?->getFieldDefinitions() as $elementName => $fd) {
            $elementValue = $blockElement[$elementName] ?? null;
            if (!$elementValue) {
                continue;
            }

            $adapter = $this->dataAdapterService->getDataAdapter($fd->getFieldType());
            $collectionData[$elementName] = $adapter->getDataForSetter(
                $element,
                $fd,
                $elementName,
                [$elementName => $elementValue],
                $fieldContextData
            );
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
