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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;
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
        // Resolve and validate every removal before the first owner is written, so that a rejected
        // removal leaves no owner changed.
        $removals = [];
        foreach (array_diff($dataToProcess['originals'], $dataToProcess['changed']) as $id) {
            $remoteObject = $this->concreteObjectResolver->getById($id);
            if ($remoteObject === null) {
                continue;
            }

            $removals[] = [$remoteObject, $this->resolveRemoval($remoteObject, $element, $getter, $ownerFieldName)];
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
     * hold the element and nothing has to change.
     *
     * @return array{value: mixed}|null
     *
     * @throws ValidationException
     */
    private function resolveRemoval(
        Concrete $remoteObject,
        Concrete $element,
        string $getter,
        string $ownerFieldName
    ): ?array {
        $currentData = method_exists($remoteObject, $getter) ? $remoteObject->$getter() : null;
        if ($currentData === null) {
            return null;
        }

        if (!is_array($currentData)) {
            if ($currentData->getId() !== $element->getId()) {
                return null;
            }

            $this->assertRemovalAllowed($remoteObject, $element, $ownerFieldName, null);

            return ['value' => null];
        }

        foreach ($currentData as $index => $relation) {
            if ($relation->getId() === $element->getId()) {
                unset($currentData[$index]);
                $this->assertRemovalAllowed($remoteObject, $element, $ownerFieldName, $currentData);

                return ['value' => $currentData];
            }
        }

        return null;
    }

    /**
     * The owner is saved with its forward relation cleared; if that field is mandatory the save
     * would fail validation, so refuse the removal up front with a message that names the cause.
     *
     * @param array<int, mixed>|null $remainingData the owner's relation data after the removal,
     *                                              null for a cleared single relation
     *
     * @throws ValidationException
     */
    private function assertRemovalAllowed(
        Concrete $remoteObject,
        Concrete $element,
        string $ownerFieldName,
        ?array $remainingData
    ): void {
        $ownerFieldDefinition = $remoteObject->getClass()->getFieldDefinition($ownerFieldName);
        if ($ownerFieldDefinition === null || !$ownerFieldDefinition->getMandatory() || !empty($remainingData)) {
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

    private function handleAddCurrentData(
        Concrete $remoteObject,
        Concrete $element,
        string $getter,
        string $setter,
        string $ownerFieldName
    ): void {
        $currentData = method_exists($remoteObject, $getter) ? $remoteObject->$getter() : null;
        if (is_array($currentData)) {
            $currentData[] = $element;
        } else {
            $currentData = $element;
        }
        $remoteObject->$setter($currentData);

        $this->saveRemoteObject($remoteObject, $element, 'added', $ownerFieldName);
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
