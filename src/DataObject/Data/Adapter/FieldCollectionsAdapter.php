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
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class FieldCollectionsAdapter implements SetterDataInterface
{
    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService,
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
            $collectionData = [];
            $collectionKey = $collectionRaw['type'];
            $oIndex = $collectionRaw['oIndex'] ?? null;
            $blockElement = $rawBlockElement['data'] ?? null;
            $collectionDef = Fieldcollection\Definition::getByKey($collectionKey);

            foreach ($collectionDef?->getFieldDefinitions() as $elementName => $fd) {
                $fieldContextData = $this->createFieldContextData($element, $fieldDefinition, $contextData);
                $adapter = $this->dataAdapterService->getDataAdapter($fieldDefinition->getFieldType());

                $collectionData[$elementName] = $adapter->getDataForSetter(
                    $element,
                    $fieldDefinition,
                    $blockElement,
                    [$blockElement => $blockElement],
                    $fieldContextData
                );
            }

            $collectionClass =
                '\\Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\' . ucfirst($collectionRaw['type']);
            /** @var AbstractData $collection */
            $collection = Pimcore::getContainer()?->get('pimcore.model.factory')?->build($collectionClass);
            $collection->setObject($element);
            $collection->setIndex($count);
            $collection->setFieldname($fieldDefinition->getName());
            $collection->setValues($collectionData);

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
        $object = $contextData?->getObjectbrick() ?? $element;

        return new FieldContextData(
            null,
            $object->get($fieldDefinition->getName()),
            $contextData?->getLanguage()
        );
    }
}
