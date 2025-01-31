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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\RelationData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\DataObject\FieldKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatchDataKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatcherActions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use function is_array;

/**
 * @internal
 */
final readonly class RelationDataService implements RelationDataServiceInterface
{
    use ElementProviderTrait;
    use ValidateObjectDataTrait;

    public function __construct(
        private PatchServiceInterface $patchService
    ) {
    }

    /**
     * @param ElementInterface[] $relations
     *
     * @return RelationData[]
     */
    public function getRelationElementsData(array $relations): array
    {
        $data = [];

        foreach ($relations as $relation) {
            $data[] = $this->getRelationElementData($relation);
        }

        return $data;
    }

    public function getAdvancedRelationElementData(ElementMetadata|ObjectMetadata $relation): array
    {

        return [
            'element' => $this->getRelationElementData($relation->getElement()),
            'fieldName' => $relation->getFieldname(),
            'columns' => $relation->getColumns(),
            'data' => $relation->getData(),
        ];
    }

    public function getSetterData(
        Concrete $element,
        Data $fieldDefinition,
        DataNormalizerInterface $adapter,
        bool $isPatch,
        ?array $fieldData,
        ?FieldContextData $contextData,
        bool $isAdvanced = false
    ): ?array {
        if (!is_array($fieldData)) {
            return null;
        }

        $relationData = $isPatch ? $fieldData[PatchDataKeys::DATA->value] : $fieldData;
        if (!$isPatch || !$this->isPatchAction($fieldData[PatchDataKeys::ACTION->value])) {
            return $relationData;
        }

        $currentValues = $adapter->normalize(
            $this->getValidFieldValue($element, $fieldDefinition->getName(), $contextData),
            $fieldDefinition
        );
        $existingValues = $this->getRelationExistingData($currentValues, $isAdvanced);

        return $this->patchService->handlePatchDataField(
            $fieldData,
            $existingValues,
            $isAdvanced ? ElementTypes::TYPE_ELEMENT : null
        );
    }

    private function getRelationElementData(ElementInterface $relation): RelationData
    {
        return new RelationData(
            $relation->getId(),
            $this->getElementType($relation, true),
            $this->getSubType($relation),
            $relation->getRealFullPath(),
            $this->getPublished($relation)
        );
    }

    private function getSubType(ElementInterface $element): string
    {
        if ($element instanceof Concrete) {
            return $element->getClassName();
        }

        return $element->getType();
    }

    private function getPublished(ElementInterface $element): ?bool
    {
        if ($element instanceof Concrete || $element instanceof Document) {
            return $element->getPublished();
        }

        return null;
    }

    private function isPatchAction(?string $action = null): bool
    {
        return !($action === null || $action === PatcherActions::REPLACE->value);
    }

    private function getRelationExistingData(array $existingValues, bool $isAdvanced): array
    {
        $existingData =[];
        if ($isAdvanced) {
            foreach ($existingValues as $index => $existingRelation) {
                $existingValues[$index][ElementTypes::TYPE_ELEMENT] = $this->getExistingElementData(
                    $existingRelation[ElementTypes::TYPE_ELEMENT]
                );
            }

            return $existingData;
        }

        foreach ($existingValues as $existingRelation) {
            $existingData[] = $this->getExistingElementData($existingRelation);
        }

        return $existingData;
    }

    private function getExistingElementData(RelationData $relationData): array
    {
        return [
            FieldKeys::ID_KEY->value => $relationData->getId(),
            FieldKeys::TYPE_KEY->value => $relationData->getType(),
            FieldKeys::SUBTYPE_KEY->value => $relationData->getSubtype(),
            FieldKeys::FULL_PATH_KEY->value => $relationData->getFullPath(),
            FieldKeys::IS_PUBLISHED_KEY->value => $relationData->getIsPublished(),
        ];
    }
}
