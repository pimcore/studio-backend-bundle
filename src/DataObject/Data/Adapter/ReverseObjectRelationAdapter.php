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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;
use function sprintf;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ReverseObjectRelationAdapter implements SetterDataInterface
{
    public function __construct(
        private ClassDefinitionResolverInterface $classDefinitionResolver,
        private ConcreteObjectResolverInterface $concreteObjectResolver,
        private LoggerInterface $pimcoreLogger,
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
        ?FieldContextData $contextData = null
    ): null {
        if (!$fieldDefinition instanceof ReverseObjectRelation) {
            return null;
        }

        $relationData = $data[$key];
        $remoteClass = $this->classDefinitionResolver->getByName($fieldDefinition->getOwnerClassName());
        if ($remoteClass === null) {
            return null;
        }

        //TODO: Remove the unpublished settings once the context is defined
        $doUnpublished = DataObject::doHideUnpublished();
        DataObject::setHideUnpublished(false);
        $ownerFieldName = $fieldDefinition->getOwnerFieldName();
        $relations = $element->getRelationData($ownerFieldName, false, $remoteClass->getId());
        $this->processRemoteOwnerRelations($element, $relations, $relationData, $ownerFieldName);
        DataObject::setHideUnpublished($doUnpublished);

        return null;
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
        foreach (array_diff($dataToProcess['originals'], $dataToProcess['changed']) as $id) {
            $remoteObject = $this->concreteObjectResolver->getById($id);
            if ($remoteObject === null) {
                continue;
            }

            $this->handleDeleteCurrentData($remoteObject, $element, $getter, $setter);
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

    private function handleDeleteCurrentData(
        Concrete $remoteObject,
        Concrete $element,
        string $getter,
        string $setter
    ): void {
        $currentData = method_exists($remoteObject, $getter) ? $remoteObject->$getter() : null;
        if ($currentData === null) {
            return;
        }

        if (!is_array($currentData) && $currentData->getId() === $element->getId()) {
            $remoteObject->$setter(null);

            return;
        }

        foreach ($currentData as $index => $relation) {
            if ($relation->getId() === $element->getId()) {
                unset($currentData[$index]);
                $remoteObject->$setter($currentData);

                break;
            }
        }
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
        } catch (Exception $e) {
            $this->pimcoreLogger->error('Failed to save remote object', ['exception' => $e]);
        }
    }
}
