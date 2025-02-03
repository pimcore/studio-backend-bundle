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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidDataTypeException;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function get_class;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class BlockAdapter implements SetterDataInterface, DataNormalizerInterface
{
    use ValidateObjectDataTrait;

    public function __construct(
        private DataAdapterServiceInterface $dataAdapterService,
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
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?array {
        if (!$fieldDefinition instanceof Block) {
            return null;
        }

        $blockData = $data[$key];

        return $this->processBlockData($element, $user, $fieldDefinition, $blockData, $isPatch, $contextData);
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
                $resultItem[$key] = $this->dataService->getNormalizedValue(
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
        UserInterface $user,
        Block $fieldDefinition,
        array $blockData,
        bool $isPatch,
        ?FieldContextData $contextData = null,
    ): array {
        $resultBlockData = [];
        foreach ($blockData as $rawBlockElement) {
            $resultElement = $this->processBlockElement(
                $element,
                $user,
                $fieldDefinition,
                $rawBlockElement,
                $isPatch,
                $contextData,
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
        UserInterface $user,
        Block $fieldDefinition,
        array $rawBlockElement,
        bool $isPatch,
        ?FieldContextData $contextData = null
    ): array {
        $resultElement = [];
        $fieldDefinitions = $fieldDefinition->getFieldDefinitions();
        $fieldContextData = $this->createFieldContextData($element, $fieldDefinition, $contextData);

        foreach ($fieldDefinitions as $elementName => $fd) {
            $adapter = $this->dataAdapterService->tryDataAdapter($fd->getFieldType());
            if (!$adapter) {
                continue;
            }

            $value = $this->createBlockElement(
                $adapter,
                $element,
                $user,
                $fd,
                $elementName,
                $isPatch,
                $rawBlockElement,
                $fieldContextData,
            );
            if (!$value) {
                continue;
            }

            $resultElement[$elementName] = $value;
        }

        return $resultElement;
    }

    /**
     * @throws Exception
     */
    private function createBlockElement(
        SetterDataInterface $adapter,
        Concrete $element,
        UserInterface $user,
        Data $fieldDefinition,
        string $elementName,
        bool $isPatch,
        ?array $blockElement,
        ?FieldContextData $fieldContextData = null
    ): ?BlockElement {
        $elementType = $fieldDefinition->getFieldtype();
        $elementData = $blockElement[$elementName] ?? null;

        $data = $adapter->getDataForSetter(
            $element,
            $fieldDefinition,
            $elementName,
            [$elementName => $elementData],
            $user,
            $fieldContextData,
            $isPatch
        );
        if (!$this->validateEncryptedField($fieldDefinition, $data)) {
            return null;
        }

        return new BlockElement(
            $elementName,
            $elementType,
            $data
        );
    }
}
