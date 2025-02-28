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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\LocalizedFieldsAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\ClassData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SearchPreviewDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectDraftData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementSaveTasks;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Model\DataObject as DataObjectModel;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EqualComparisonInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version as DataObjectVersionModal;
use Pimcore\Normalizer\NormalizerInterface;
use function array_key_exists;

/**
 * @internal
 */
final readonly class DataService implements DataServiceInterface
{
    use ValidateObjectDataTrait;

    public function __construct(
        private ClassDefinitionResolverInterface $classDefinitionResolver,
        private DataAdapterServiceInterface $dataAdapterService,
        private InheritanceServiceInterface $inheritanceService,
        private WorkflowDetailsServiceInterface $workflowDetailsService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function setObjectDetailData(
        DataObjectFolder|DataObject|DataObjectVersion $dataObject,
        DataObjectModel $element,
        ?DataObjectVersionModal $version = null,
    ): void {
        $dataObject->setHasWorkflowAvailable($this->workflowDetailsService->hasElementWorkflows($element));
        if ($dataObject instanceof DataObjectFolder || !$element instanceof Concrete) {
            return;
        }

        $class = $this->getValidClass($this->classDefinitionResolver, $element->getClassId());
        $classData = $this->getObjectClassData($class);
        $fieldDefinitions = $class->getFieldDefinitions();

        $dataObject->setAllowInheritance($classData->getAllowInheritance());
        $dataObject->setAllowVariants($classData->getAllowVariants());
        $dataObject->setShowVariants($classData->getShowVariants());
        $dataObject->setHasPreview($classData->getHasPreview());
        $dataObject->setObjectData($this->getNormalizedObjectData($element, $fieldDefinitions));

        if ($dataObject instanceof DataObject) {
            $dataObject->setDraftData($this->getDraftData($element, $version));

            if ($dataObject->getAllowInheritance()) {
                $dataObject->setInheritanceData(
                    $this->inheritanceService->getInheritanceData($element, $fieldDefinitions)
                );
            }
        }
    }

    public function getNormalizedValue(
        mixed $value,
        Data $fieldDefinition
    ): mixed {
        if (!$fieldDefinition instanceof NormalizerInterface) {
            return null;
        }

        $adapter = $this->dataAdapterService->tryDataAdapter($fieldDefinition->getFieldType());
        if ($adapter instanceof DataNormalizerInterface) {
            return $adapter->normalize($value, $fieldDefinition);
        }

        return $fieldDefinition->normalize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviewObjectData(DataObjectModel $dataObject): array
    {
        if (!$dataObject instanceof Concrete) {
            return [];
        }

        $class = $this->getValidClass($this->classDefinitionResolver, $dataObject->getClassId());
        $data = [];
        foreach ($class->getFieldDefinitions() as $key => $fieldDefinition) {
            $data = $this->getPreviewFieldData(
                $this->getValidFieldValue($dataObject, $key),
                $fieldDefinition,
                $data
            );
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviewFieldData(
        mixed $value,
        Data $fieldDefinition,
        array $data
    ): array {
        $adapter = $this->dataAdapterService->tryDataAdapter($fieldDefinition->getFieldType());
        if ($adapter instanceof SearchPreviewDataInterface) {
            return $adapter->getPreviewFieldData($value, $fieldDefinition, $data);
        }

        $data[$this->getPreviewFieldName($fieldDefinition)] = $fieldDefinition->getVersionPreview($value);

        return $data;
    }

    public function getPreviewFieldName(Data $fieldDefinition): string
    {
        return !empty($fieldDefinition->getTitle()) ? $fieldDefinition->getTitle() : $fieldDefinition->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function updateEditableData(Concrete $element, array $editableData, UserInterface $user): void
    {
        try {
            $class = $element->getClass();
            foreach ($editableData as $key => $value) {
                $fieldDefinition = $class->getFieldDefinition($key);
                if ($fieldDefinition === null || !array_key_exists($key, $editableData)) {
                    continue;
                }

                $adapter = $this->dataAdapterService->tryDataAdapter($fieldDefinition->getFieldtype());
                if ($adapter === null) {
                    continue;
                }

                $value = $adapter->getDataForSetter($element, $fieldDefinition, $key, $editableData, $user);
                if (!$this->validateEncryptedField($fieldDefinition, $value)) {
                    continue;
                }

                $element->setValue($key, $value);
            }

        } catch (Exception $e) {
            throw new ElementSavingFailedException($element->getId(), $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleDraftData(Concrete $draftElement, Concrete $element, ?string $task = null): void
    {
        if ($task === ElementSaveTasks::AUTOSAVE->value || $task === ElementSaveTasks::UNPUBLISH->value) {
            return;
        }

        $getter = 'get' . ucfirst(LocalizedFieldsAdapter::LOCALIZED_FIELDS_KEY);
        if (method_exists($draftElement, $getter)) {
            $draftElement->$getter()->setLoadedAllLazyData();
        }

        $class = $this->getValidClass($this->classDefinitionResolver, $element->getClassId());
        foreach ($class->getFieldDefinitions() as $fieldName => $fieldDefinition) {
            $getter = 'get' . ucfirst($fieldName);
            $oldValue = $element->$getter();
            $newValue = $draftElement->$getter();
            $isEqual = $fieldDefinition instanceof EqualComparisonInterface
                ? $fieldDefinition->isEqual($oldValue, $newValue)
                : $oldValue === $newValue;

            if (!$isEqual) {
                $draftElement->markFieldDirty($fieldName);
            }
        }
    }

    /**
     * @throws NotFoundException
     */
    private function getNormalizedObjectData(Concrete $dataObject, array $fieldDefinitions): array
    {
        $data = [];
        foreach ($fieldDefinitions as $key => $fieldDefinition) {
            $data[$key] = $this->getNormalizedValue(
                $this->getValidFieldValue($dataObject, $key),
                $fieldDefinition
            );
        }

        return $data;
    }

    private function getObjectClassData(ClassDefinition $class): ClassData
    {

        return new ClassData(
            $class->getAllowInherit(),
            $class->getAllowVariants(),
            $class->getShowVariants(),
            (bool)$class->getLinkGeneratorReference()
        );
    }

    private function getDraftData(
        DataObjectModel $dataObject,
        ?DataObjectVersionModal $version = null
    ): ?DataObjectDraftData {
        if (!$version || $dataObject->getModificationDate() < $version->getDate()) {
            return null;
        }

        return new DataObjectDraftData(
            $version->getId(),
            $version->getDate(),
            $version->isAutoSave()
        );
    }
}
