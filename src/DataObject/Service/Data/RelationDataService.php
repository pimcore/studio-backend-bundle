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

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Element\Repository\RelatedElementRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\DataObject\FieldKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatchDataKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatcherActions;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ElementInterface;
use function array_unique;
use function array_values;
use function is_array;

/**
 * @internal
 */
final readonly class RelationDataService implements RelationDataServiceInterface
{
    use ValidateObjectDataTrait;

    public function __construct(
        private ElementDataServiceInterface $elementDataService,
        private PatchServiceInterface $patchService,
        private RelatedElementRepositoryInterface $relatedElementRepository,
        private SecurityServiceInterface $securityService,
        /** @var array<string, array<string, mixed>> the workflow configuration, keyed by workflow name */
        private array $workflowConfigs,
    ) {
    }

    /**
     * Entries that are no element are skipped: a version keeps null for a related element deleted afterwards.
     *
     * @param array<ElementInterface|null> $relations
     *
     * @return RelatedElementData[]
     */
    public function getRelationElementsData(array $relations): array
    {
        $data = [];

        foreach ($relations as $relation) {
            if (!$relation instanceof ElementInterface) {
                continue;
            }

            $data[] = $this->elementDataService->getRelatedElementData($relation)->withoutRestrictedData();
        }

        return $data;
    }

    public function getAdvancedRelationElementData(ElementMetadata|ObjectMetadata $relation): array
    {

        return [
            'element' => $this->elementDataService
                ->getRelatedElementData($relation->getElement())
                ->withoutRestrictedData(),
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
        // GHSA-pg5f-74vr-w237: the rows of the repository carry no permission information, so they are
        // only used for a user who may view every element. For everybody else the target is checked.
        if (!$this->currentUserMayViewAllElements()) {
            $data = [];
            foreach ($relations as $relation) {
                if ($relation->getElement() === null) {
                    continue;
                }

                $data[] = $this->getAdvancedRelationElementData($relation);
            }

            return $data;
        }

        $idsByType = [];
        foreach ($relations as $relation) {
            [$type, $id] = $this->getTargetReference($relation);
            $idsByType[$type][] = $id;
        }

        $elementsByType = [];
        foreach ($idsByType as $type => $ids) {
            $elementsByType[$type] = $this->relatedElementRepository->getRelatedElementsData(
                $type,
                array_values(array_unique($ids))
            );
        }

        $data = [];
        foreach ($relations as $relation) {
            [$type, $id] = $this->getTargetReference($relation);
            $element = $elementsByType[$type][$id] ?? null;
            if ($element === null) {
                continue;
            }

            $data[] = [
                'element' => $element,
                'fieldName' => $relation->getFieldname(),
                'columns' => $relation->getColumns(),
                'data' => $relation->getData(),
            ];
        }

        return $data;
    }

    /**
     * Like AbstractElement::isAllowed(): an admin may view every element, unless a workflow place denies
     * the permission. Without a user nothing counts as viewable.
     */
    private function currentUserMayViewAllElements(): bool
    {
        try {
            $user = $this->securityService->getCurrentUser();
        } catch (UserNotFoundException) {
            return false;
        }

        return $user->isAdmin() && !$this->hasWorkflowPermissions();
    }

    private function hasWorkflowPermissions(): bool
    {
        foreach ($this->workflowConfigs as $config) {
            if (($config['enabled'] ?? true) === false) {
                continue;
            }

            foreach ($config['places'] ?? [] as $place) {
                if (!empty($place['permissions'])) {
                    return true;
                }
            }
        }

        return false;
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
