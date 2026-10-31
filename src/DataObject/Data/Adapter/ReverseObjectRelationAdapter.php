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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_filter;
use function array_key_exists;
use function array_unique;
use function array_values;
use function count;
use function is_array;
use function method_exists;
use function sprintf;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ReverseObjectRelationAdapter implements SetterDataInterface, DataNormalizerInterface
{
    public function __construct(
        private ClassDefinitionResolverInterface $classDefinitionResolver,
        private ConcreteObjectResolverInterface $concreteObjectResolver,
        private DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private LoggerInterface $pimcoreLogger,
        private RelationDataServiceInterface $relationDataService,
        private SecurityServiceInterface $securityService
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
    ): null {
        if (!$fieldDefinition instanceof ReverseObjectRelation) {
            return null;
        }

        $relationData = $this->relationDataService->getSetterData(
            $element,
            $fieldDefinition,
            $this,
            $isPatch,
            $data[$key],
            $contextData
        );
        $remoteClass = $this->classDefinitionResolver->getByName($fieldDefinition->getOwnerClassName());
        if ($remoteClass === null || $relationData === null) {
            return null;
        }

        $ownerFieldName = $fieldDefinition->getOwnerFieldName();
        $relations = $element->getRelationData($ownerFieldName, false, $remoteClass->getId());
        $this->processRemoteOwnerRelations($element, $relations, $relationData, $ownerFieldName);

        return null;
    }

    /**
     * @return RelatedElementData[]|null
     */
    public function normalize(
        mixed $value,
        Data $fieldDefinition
    ): ?array {
        if (!is_array($value)) {
            return null;
        }

        return $this->relationDataService->getRelationElementsData($value);
    }

    private function processRemoteOwnerRelations(
        Concrete $element,
        array $relations,
        array $newRelations,
        string $ownerFieldName
    ): void {
        $getter = 'get' . ucfirst($ownerFieldName);
        $setter = 'set' . ucfirst($ownerFieldName);
        $dataToProcess = $this->getRemoteOwnerRelations($relations, $newRelations);
        // Resolve every removal, then validate all of them against the planned values, and only then
        // write, so that a rejected removal leaves no owner changed. An owner with repeated assignments
        // is listed once per assignment; it is processed once and loses every occurrence of the element.
        $removals = [];
        $plannedValues = [];
        foreach (array_unique(array_diff($dataToProcess['originals'], $dataToProcess['changed'])) as $id) {
            $remoteObject = $this->concreteObjectResolver->getById($id);
            if ($remoteObject === null) {
                continue;
            }

            $removal = $this->resolveRemoval($remoteObject, $element, $getter);
            $removals[] = [$remoteObject, $removal];
            if ($removal !== null) {
                $plannedValues[$remoteObject->getId()] = $removal['value'];
            }
        }

        foreach ($removals as [$remoteObject, $removal]) {
            if ($removal !== null) {
                $this->assertRemovalAllowed(
                    $remoteObject,
                    $element,
                    $getter,
                    $ownerFieldName,
                    $removal['value'],
                    $plannedValues
                );
            }
        }

        foreach ($removals as [$remoteObject, $removal]) {
            if ($removal !== null) {
                $remoteObject->$setter($removal['value']);
            }
            $this->saveRemoteObject($remoteObject, $element, 'deleted', $ownerFieldName);
        }

        foreach (array_diff($dataToProcess['changed'], $dataToProcess['originals']) as $id) {
            $remoteObject = $this->concreteObjectResolver->getById($id);
            if ($remoteObject === null) {
                continue;
            }

            $this->handleAddCurrentData($remoteObject, $element, $getter, $setter, $ownerFieldName);
        }
    }

    private function getRemoteOwnerRelations(array $relations, array $newRelations): array
    {
        $originals = [];
        $changed = [];
        foreach ($relations as $relation) {
            $originals[] = $relation['dest_id'];
        }
        foreach ($newRelations as $newRelation) {
            $changed[] = $newRelation['id'];
        }

        return [
            'originals' => $originals,
            'changed' => $changed,
        ];
    }

    /**
     * Returns the owner's relation value after removing the element, or null when the owner does not
     * hold the element and nothing has to change. Entries are matched by element type and id, so an
     * asset or document sharing the numeric id stays, and retained entries keep their metadata wrapper.
     *
     * @return array{value: mixed}|null
     */
    private function resolveRemoval(Concrete $remoteObject, Concrete $element, string $getter): ?array
    {
        $currentData = method_exists($remoteObject, $getter) ? $remoteObject->$getter() : null;
        if ($currentData === null) {
            return null;
        }

        if (!is_array($currentData)) {
            return $this->isRelationToElement($currentData, $element) ? ['value' => null] : null;
        }

        $remainingData = array_values(array_filter(
            $currentData,
            fn (mixed $relation): bool => !$this->isRelationToElement($relation, $element)
        ));
        if (count($remainingData) === count($currentData)) {
            return null;
        }

        return ['value' => $remainingData];
    }

    private function isRelationToElement(mixed $relation, Concrete $element): bool
    {
        return match (true) {
            $relation instanceof ObjectMetadata => $relation->getObjectId() === $element->getId(),
            $relation instanceof ElementMetadata => $relation->getElementType() === ElementTypes::TYPE_OBJECT
                && $relation->getElementId() === $element->getId(),
            default => $relation instanceof AbstractObject && $relation->getId() === $element->getId(),
        };
    }

    /**
     * The owner is saved with its forward relation cleared; if that field is mandatory the save
     * would fail validation, so refuse the removal up front with a message that names the cause.
     *
     * @param array<int, mixed>|null $remainingData the owner's relation data after the removal,
     *                                              null for a cleared single relation
     * @param array<int, mixed> $plannedValues the post-removal values of every owner in this edit, by id
     *
     * @throws ValidationException
     */
    private function assertRemovalAllowed(
        Concrete $remoteObject,
        Concrete $element,
        string $getter,
        string $ownerFieldName,
        ?array $remainingData,
        array $plannedValues
    ): void {
        if ($remoteObject->getOmitMandatoryCheck()) {
            // Core skips the mandatory check for this owner (e.g. it is unpublished), so does the removal.
            return;
        }

        $ownerFieldDefinition = $remoteObject->getClass()->getFieldDefinition($ownerFieldName);
        if (
            $ownerFieldDefinition === null
            || !$ownerFieldDefinition->getMandatory()
            || !$ownerFieldDefinition->isEmpty($remainingData)
            || $this->hasInheritedValue($remoteObject, $ownerFieldDefinition, $getter, $plannedValues)
        ) {
            return;
        }

        throw new ValidationException(
            sprintf(
                'Cannot remove the relation to object [ %d ] from the reverse side:'
                . ' field [ %s ] of the owner object [ %d ] is mandatory',
                $element->getId(),
                $ownerFieldName,
                $remoteObject->getId()
            )
        );
    }

    /**
     * Mirrors core validation, which accepts an empty inheritable field when an ancestor provides a
     * value. Ancestors that lose their value in the same edit count with their planned value.
     *
     * @param array<int, mixed> $plannedValues
     */
    private function hasInheritedValue(
        Concrete $remoteObject,
        Data $ownerFieldDefinition,
        string $getter,
        array $plannedValues
    ): bool {
        if (!$remoteObject->getClass()->getAllowInherit() || !$ownerFieldDefinition->supportsInheritance()) {
            return false;
        }

        $parent = $remoteObject->getNextParentForInheritance();
        while ($parent !== null) {
            if (array_key_exists($parent->getId(), $plannedValues)) {
                $value = $plannedValues[$parent->getId()];
            } elseif (method_exists($parent, $getter)) {
                $value = $this->dataObjectServiceResolver->useInheritedValues(
                    false,
                    static fn (): mixed => $parent->$getter()
                );
            } else {
                return false;
            }

            if (!$ownerFieldDefinition->isEmpty($value)) {
                return true;
            }

            $parent = $parent->getNextParentForInheritance();
        }

        return false;
    }

    private function handleAddCurrentData(
        Concrete $remoteObject,
        Concrete $element,
        string $getter,
        string $setter,
        string $ownerFieldName
    ): void {
        $currentData = method_exists($remoteObject, $getter) ? $remoteObject->$getter() : null;
        $newEntry = $this->createOwnerEntry($remoteObject, $element, $ownerFieldName);
        if (is_array($currentData)) {
            $currentData[] = $newEntry;
        } else {
            $currentData = $newEntry;
        }
        $remoteObject->$setter($currentData);

        $this->saveRemoteObject($remoteObject, $element, 'added', $ownerFieldName);
    }

    /**
     * Advanced many-to-many owner fields only accept metadata wrappers around their targets, any other
     * owner field stores the object itself.
     */
    private function createOwnerEntry(
        Concrete $remoteObject,
        Concrete $element,
        string $ownerFieldName
    ): Concrete|ObjectMetadata|ElementMetadata {
        $ownerFieldDefinition = $remoteObject->getClass()->getFieldDefinition($ownerFieldName);

        return match (true) {
            $ownerFieldDefinition instanceof AdvancedManyToManyObjectRelation => new ObjectMetadata(
                $ownerFieldName,
                $ownerFieldDefinition->getColumnKeys(),
                $element
            ),
            $ownerFieldDefinition instanceof AdvancedManyToManyRelation => new ElementMetadata(
                $ownerFieldName,
                $ownerFieldDefinition->getColumnKeys(),
                $element
            ),
            default => $element,
        };
    }

    /**
     * @throws ValidationException
     */
    private function saveRemoteObject(
        Concrete $remoteObject,
        Concrete $element,
        string $action,
        string $fieldName
    ): void {
        try {
            $actionTarget = 'to';
            if ($action === 'deleted') {
                $actionTarget = 'from';
            }
            $remoteObject->setUserModification($this->securityService->getCurrentUser()->getId());
            $remoteObject->save();
            $this->pimcoreLogger->debug(
                sprintf(
                    'Saved object id [ %d ] by remote modification through [ %d ], Action: %s [ %d ] %s [ %s ]',
                    $remoteObject->getId(),
                    $element->getId(),
                    $action,
                    $element->getId(),
                    $actionTarget,
                    $fieldName
                )
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->pimcoreLogger->error('Failed to save remote object', ['exception' => $e]);
        }
    }
}
