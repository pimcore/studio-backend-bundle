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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Element\Repository\RelatedElementRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSummaryDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\DataObject\FieldKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatchDataKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatcherActions;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\LightweightRelationLoadingInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\OptimizedAdminLoadingInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ElementInterface;
use ReflectionMethod;
use function array_map;
use function array_unique;
use function array_values;
use function is_array;
use function method_exists;
use function ucfirst;

/**
 * @internal
 */
final readonly class RelationDataService implements RelationDataServiceInterface
{
    use ValidateObjectDataTrait;

    public function __construct(
        private ElementDataServiceInterface $elementDataService,
        private PatchServiceInterface $patchService,
        private ConcreteObjectResolverInterface $concreteObjectResolver,
        private RelatedElementRepositoryInterface $relatedElementRepository
    ) {
    }

    /**
     * @param ElementInterface[] $relations
     *
     * @return RelatedElementData[]
     */
    public function getRelationElementsData(array $relations): array
    {
        $data = [];

        foreach ($relations as $relation) {
            $data[] = $this->elementDataService->getRelatedElementData($relation);
        }

        return $data;
    }

    public function getLightweightRelationData(Concrete $element, Data $fieldDefinition, string $key): ?array
    {
        if (!$this->supportsLightweightLoading($fieldDefinition) || !$this->usesGeneratedGetter($element, $key)) {
            return null;
        }

        // like the getter, an empty own value is inherited while inherited values are in use
        $origin = $this->findSummaryOrigin(
            $element,
            $fieldDefinition,
            $key,
            $this->concreteObjectResolver->doGetInheritedValues($element)
        );
        if ($origin === null) {
            return null;
        }

        return $this->toRelatedElementData($origin['summaries']);
    }

    public function getLightweightInheritanceData(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): ?InheritanceData {
        if (
            !$this->supportsLightweightLoading($fieldDefinition) ||
            !$this->usesGeneratedGetter($element, $key) ||
            !$fieldDefinition->supportsInheritance() ||
            $contextData?->getContextObject() !== null ||
            $contextData?->getLanguage() !== null
        ) {
            return null;
        }

        $origin = $this->findSummaryOrigin($element, $fieldDefinition, $key, true);
        if ($origin === null) {
            return null;
        }

        $inherited = $origin['object']->getId() !== $element->getId();
        $inheritedValue = null;
        if ($contextData?->shouldResolveInheritedValue()) {
            // an inherited value is the origin already found; an own value hides the nearest ancestor's value
            $parentOrigin = $inherited ? $origin : $this->findParentSummaryOrigin($element, $fieldDefinition, $key);
            if ($parentOrigin === null && $element->getNextParentForInheritance() !== null) {
                return null;
            }

            if ($parentOrigin !== null && $parentOrigin['summaries'] !== []) {
                $inheritedValue = $this->toRelatedElementData($parentOrigin['summaries']);
            }
        }

        return new InheritanceData($origin['object']->getId(), $inherited, true, $inheritedValue);
    }

    /**
     * @phpstan-assert-if-true LightweightRelationLoadingInterface $fieldDefinition
     */
    private function supportsLightweightLoading(Data $fieldDefinition): bool
    {
        return $this->elementDataService instanceof ElementSummaryDataServiceInterface
            && $fieldDefinition instanceof OptimizedAdminLoadingInterface
            && $fieldDefinition->isOptimizedAdminLoading()
            && $fieldDefinition instanceof LightweightRelationLoadingInterface;
    }

    /**
     * The relation rows only describe what the generated getter returns through the generic get(); a getter or
     * a get() of the project (a model override or a custom parent class) may return something else, so the
     * value is then read as usual.
     */
    private function usesGeneratedGetter(Concrete $element, string $key): bool
    {
        $getter = 'get' . ucfirst($key);
        if (!method_exists($element, $getter)) {
            return false;
        }

        if ((new ReflectionMethod($element, 'get'))->getDeclaringClass()->getName() !== AbstractObject::class) {
            return false;
        }

        return (new ReflectionMethod($element, $getter))->getDeclaringClass()->getName()
            === 'Pimcore\\Model\\DataObject\\' . ucfirst($element->getClassName());
    }

    /**
     * Walks up from the object to the nearest ancestor with relations, like the inheritance resolution does
     * with the field values; without any, the terminal ancestor is returned with an empty list. Returns null
     * when the value of a visited object is already in memory (a version, a draft or unsaved changes), which
     * only the regular loading can describe.
     *
     * @return array{object: Concrete, summaries: list<array<string, mixed>>}|null
     */
    private function findSummaryOrigin(
        Concrete $object,
        Data&LightweightRelationLoadingInterface $fieldDefinition,
        string $key,
        bool $walkParents
    ): ?array {
        if ($object->isLazyKeyLoaded($key)) {
            return null;
        }

        $summaries = $fieldDefinition->loadRelationSummaries($object);
        if ($summaries !== [] || !$walkParents) {
            return ['object' => $object, 'summaries' => $summaries];
        }

        $parent = $object->getNextParentForInheritance();
        if ($parent === null) {
            return ['object' => $object, 'summaries' => $summaries];
        }

        return $this->findSummaryOrigin($parent, $fieldDefinition, $key, true);
    }

    /**
     * @return array{object: Concrete, summaries: list<array<string, mixed>>}|null
     */
    private function findParentSummaryOrigin(
        Concrete $object,
        Data&LightweightRelationLoadingInterface $fieldDefinition,
        string $key
    ): ?array {
        $parent = $object->getNextParentForInheritance();

        return $parent === null ? null : $this->findSummaryOrigin($parent, $fieldDefinition, $key, true);
    }

    /**
     * @return RelatedElementData[]
     */
    private function toRelatedElementData(array $summaries): array
    {
        /** @var ElementSummaryDataServiceInterface $service */
        $service = $this->elementDataService;

        return array_map($service->getRelatedElementDataFromSummary(...), $summaries);
    }

    public function getAdvancedRelationElementData(ElementMetadata|ObjectMetadata $relation): array
    {

        return [
            'element' => $this->elementDataService->getRelatedElementData($relation->getElement()),
            'fieldName' => $relation->getFieldname(),
            'columns' => $relation->getColumns(),
            'data' => $relation->getData(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAdvancedRelationElementsData(array $relations): array
    {
        $idsByType = [];
        foreach ($relations as $relation) {
            [$type, $id] = $this->getTargetReference($relation);
            $idsByType[$type][] = $id;
        }

        $summariesByType = [];
        foreach ($idsByType as $type => $ids) {
            $summariesByType[$type] = $this->relatedElementRepository->getRelatedElementSummaries(
                $type,
                array_values(array_unique($ids))
            );
        }

        $data = [];
        foreach ($relations as $relation) {
            [$type, $id] = $this->getTargetReference($relation);
            $summary = $summariesByType[$type][$id] ?? null;
            if ($summary === null) {
                continue;
            }

            $data[] = [
                'element' => $this->getRelatedElementDataFromSummary($summary, $relation),
                'fieldName' => $relation->getFieldname(),
                'columns' => $relation->getColumns(),
                'data' => $relation->getData(),
            ];
        }

        return $data;
    }

    /**
     * The summary carries everything the row shows; view access is decided like for the lightweight relation
     * loading, which only loads the element when a workflow, a permission listener or a project class could
     * decide differently. A decorated element data service without summary support loads the element.
     *
     * @param array{id: int, parentId: int, type: string, subtype: string, className: string|null, fullpath: string, filename: string, published: bool} $summary
     */
    private function getRelatedElementDataFromSummary(
        array $summary,
        ElementMetadata|ObjectMetadata $relation
    ): RelatedElementData {
        if ($this->elementDataService instanceof ElementSummaryDataServiceInterface) {
            return $this->elementDataService->getRelatedElementDataFromSummary($summary);
        }

        return $this->elementDataService->getRelatedElementData($relation->getElement());
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
        ) ?? [];
        $existingValues = $this->getRelationExistingData($currentValues, $isAdvanced);

        return $this->patchService->handlePatchDataField(
            $fieldData,
            $existingValues,
            $isAdvanced ? ElementTypes::TYPE_ELEMENT : null,
            $this->allowsMultipleAssignments($fieldDefinition)
        );
    }

    /**
     * Same check as AbstractRelations::performMultipleAssignmentCheck(), which rejects repeated relations on save.
     */
    private function allowsMultipleAssignments(Data $fieldDefinition): bool
    {
        return method_exists($fieldDefinition, 'getAllowMultipleAssignments')
            && $fieldDefinition->getAllowMultipleAssignments();
    }

    /**
     * @return array{0: string, 1: int} core element type and id of the relation target
     */
    private function getTargetReference(ElementMetadata|ObjectMetadata $relation): array
    {
        if ($relation instanceof ObjectMetadata) {
            return [ElementTypes::TYPE_OBJECT, $relation->getObjectId()];
        }

        return [(string) $relation->getElementType(), (int) $relation->getElementId()];
    }

    private function isPatchAction(?string $action = null): bool
    {
        return !($action === null || $action === PatcherActions::REPLACE->value);
    }

    private function getRelationExistingData(array $existingValues, bool $isAdvanced): array
    {
        $existingData = [];
        if ($isAdvanced) {
            foreach ($existingValues as $existingRelation) {
                $existingRelation[ElementTypes::TYPE_ELEMENT] = $this->getExistingElementData(
                    $existingRelation[ElementTypes::TYPE_ELEMENT]
                );
                $existingData[] = $existingRelation;
            }

            return $existingData;
        }

        foreach ($existingValues as $existingRelation) {
            $existingData[] = $this->getExistingElementData($existingRelation);
        }

        return $existingData;
    }

    private function getExistingElementData(RelatedElementData $relationData): array
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
