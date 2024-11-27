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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidDataTypeException;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function get_class;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class BlockAdapter implements SetterDataInterface, DataNormalizerInterface
{
    public function __construct(
        private DataServiceInterface $dataService
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
    ): ?array {
        if (!$fieldDefinition instanceof Block) {
            return null;
        }

        $blockData = $data[$key];

        return $this->processBlockData($element, $fieldDefinition, $blockData, $contextData);
    }

    public function normalize(
        mixed $value,
        Data $fieldDefinition
    ): ?array {
        if (!is_array($value)) {
            return null;
        }

        $resultItems = [];
        if (!$fieldDefinition instanceof Block) {
            throw new InvalidDataTypeException(Block::class, get_class($fieldDefinition));
        }
        $fieldDefinitions = $fieldDefinition->getFieldDefinitions();
        foreach ($value as $block) {
            $resultItem = [];

            /** @var BlockElement $fieldValue */
            foreach ($block as $key => $fieldValue) {
                $blockDefinition = $fieldDefinitions[$key];
                $resultItems[$key] = $this->dataService->getNormalizedValue(
                    $fieldValue->getData(),
                    $blockDefinition,
                );
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
    private function processBlockData(
        Concrete $element,
        Block $fieldDefinition,
        array $blockData,
        FieldContextData $contextData = null
    ): array {
        $resultBlockData = [];
        foreach ($blockData as $rawBlockElement) {
            $resultElement = $this->processBlockElement(
                $element,
                $fieldDefinition,
                $rawBlockElement,
                $contextData
            );
            $resultBlockData[] = $resultElement;
        }

        return $resultBlockData;
    }

    /**
     * @throws Exception
     */
    private function processBlockElement(
        Concrete $element,
        Block $fieldDefinition,
        array $rawBlockElement,
        FieldContextData $contextData = null
    ): array {
        $resultElement = [];
        $blockElement = $rawBlockElement['data'] ?? null;
        $fieldDefinitions = $fieldDefinition->getFieldDefinitions();
        $fieldContextData = $this->createFieldContextData($element, $fieldDefinition, $contextData);

        foreach ($fieldDefinitions as $elementName => $fd) {
            $resultElement[$elementName] = $this->createBlockElement(
                $element,
                $fd,
                $elementName,
                $blockElement,
                $fieldContextData
            );
        }

        return $resultElement;
    }

    /**
     * @throws Exception
     */
    private function createBlockElement(
        Concrete $element,
        Data $fieldDefinition,
        string $elementName,
        ?array $blockElement,
        ?FieldContextData $fieldContextData = null
    ): BlockElement {
        $elementType = $fieldDefinition->getFieldtype();
        $elementData = $blockElement[$elementName] ?? null;

        return new BlockElement(
            $elementName,
            $elementType,
            $this->dataService->getAdapterSetterValue(
                $element,
                $fieldDefinition,
                $elementName,
                [$elementName => $elementData],
                $fieldContextData
            )
        );
    }
}
