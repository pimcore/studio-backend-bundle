<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Pimcore\Model\DataObject\Data\BlockElement;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class BlockAdapter implements SetterDataInterface
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
    ): ?array {
        if (!$fieldDefinition instanceof Block) {
            return null;
        }

        $blockData = $data[$key];
        return $this->processBlockData($element, $fieldDefinition, $blockData, $contextData);
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

        $adapter = $this->dataAdapterService->getDataAdapter($elementType);
        $blockData = $adapter->getDataForSetter(
            $element,
            $fieldDefinition,
            $elementName,
            [$elementName => $elementData],
            $fieldContextData
        );

        return new BlockElement($elementName, $elementType, $blockData);
    }
}