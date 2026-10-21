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

namespace Pimcore\Bundle\StudioBackendBundle\Patcher\Service;

use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\SelectionProcessingMode;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\PatchFolderMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\PatchMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\CoauthorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementIndexServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\FieldValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\PatchFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\UpdateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\DataObject\FieldKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatchDataKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatcherActions;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;
use function array_is_list;
use function array_key_exists;
use function count;
use function is_array;
use function sprintf;

/**
 * @internal
 */
final readonly class PatchService implements PatchServiceInterface
{
    use ValidateObjectDataTrait;

    public function __construct(
        private AdapterLoaderInterface $adapterLoader,
        private DataAdapterServiceInterface $dataAdapterService,
        private ElementServiceInterface $elementService,
        private JobExecutionAgentInterface $jobExecutionAgent,
        private ElementIndexServiceInterface $indexService,
        private ElementSaveServiceInterface $elementSaveService,
        private SecurityServiceInterface $securityService,
        private CoauthorServiceInterface $coauthorService,
    ) {
    }

    /**
     * @throws ForbiddenException|ElementSavingFailedException|NotFoundException|InvalidArgumentException
     */
    public function patch(
        string $elementType,
        array $patchData,
        UserInterface $user,
    ): ?int {
        if (count($patchData) > 1) {
            return $this->patchAsynchronously($elementType, $patchData, $user);
        }

        $element = $this->elementService->getAllowedElementById($elementType, $patchData[0]['id'], $user);
        $this->patchElement($element, $elementType, $patchData[0], $user);

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function patchFolder(
        int $folderId,
        string $elementType,
        PatchFolderParameter $patchFolderParameter,
        UserInterface $user,
    ): int {
        $classId = $patchFolderParameter->getClassId();
        if ($elementType === ElementTypes::TYPE_OBJECT && $classId === null) {
            throw new InvalidArgumentException('Class ID must be provided for object folder patching');
        }

        $job = new Job(
            Jobs::PATCH_FOLDER_ELEMENTS->value,
            [
                new JobStep(
                    JobSteps::ELEMENT_FOLDER_PATCHING->value,
                    PatchFolderMessage::class,
                    '',
                    [
                        StepConfig::CONFIG_FILTERS->value => $patchFolderParameter->getFilters(),
                        StepConfig::ELEMENT_CLASS_ID->value => $classId ?? '',
                    ],
                    SelectionProcessingMode::ONCE
                ),
                new JobStep(
                    JobSteps::ELEMENT_PATCHING->value,
                    PatchMessage::class,
                    '',
                    [
                        StepConfig::FOLDER_TO_EXPORT->value => $folderId,
                    ]
                ),
            ],
            [new ElementDescriptor($elementType, $folderId)],
            [$folderId => $patchFolderParameter->getData()],
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_CONTINUE_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    /**
     * @throws ElementSavingFailedException|FieldValidationFailedException|ForbiddenException|InvalidArgumentException
     */
    public function patchElement(
        ElementInterface $element,
        string $elementType,
        array $elementPatchData,
        UserInterface $user,
    ): void {
        // Writing object field data requires the workspace `save` permission. Publish/unpublish
        // (PublishAdapter) and the other adapters enforce their own permissions inside the try
        // below; their ForbiddenException is re-thrown so it is not masked as a 500.
        if ($element instanceof Concrete && isset($elementPatchData[UpdateServiceInterface::EDITABLE_DATA_KEY])) {
            $this->securityService->hasElementPermission($element, $user, ElementPermissions::SAVE_PERMISSION);
        } elseif ($element instanceof Asset) {
            // Assets have no workspace `save` permission; `publish` is their edit/write permission.
            $this->securityService->hasElementPermission($element, $user, ElementPermissions::PUBLISH_PERMISSION);
        }

        $coauthor = $this->coauthorService->extractFromPayload($elementPatchData);

        try {
            if (isset($elementPatchData[UpdateServiceInterface::EDITABLE_DATA_KEY]) && $element instanceof Concrete) {
                $this->patchEditableData(
                    $element,
                    $elementPatchData[UpdateServiceInterface::EDITABLE_DATA_KEY],
                    $user
                );

                unset($elementPatchData[UpdateServiceInterface::EDITABLE_DATA_KEY]);
            }

            $adapters = $this->adapterLoader->loadAdapters($elementType);
            foreach ($adapters as $adapter) {
                $adapter->patch($element, $elementPatchData, $user);
            }

            $save = function () use ($element, $user, $elementPatchData): void {
                $this->elementSaveService->save(
                    $element,
                    $user,
                    $elementPatchData[ElementSaveServiceInterface::INDEX_TASK] ?? null
                );

                if (isset($elementPatchData['index'])) {
                    $this->indexService->indexRelatedElements($element, $elementPatchData['index']);
                }
            };

            $this->coauthorService->runWithCoauthor($coauthor, $save);
        } catch (DuplicateFullPathException) {
            throw new ElementExistsException(
                message: sprintf('Element with full path [%s] already exists', $element->getRealFullPath())
            );
        } catch (ForbiddenException $exception) {
            throw $exception;
        } catch (ValidationException $exception) {
            throw new FieldValidationFailedException($exception->getMessage(), previous: $exception);
        } catch (Exception $exception) {
            throw new ElementSavingFailedException($element->getId(), $exception->getMessage());
        }
    }

    public function handlePatchDataField(
        array $fieldData,
        array $existingValues,
        ?string $dataKey = null,
        bool $allowRepeatedEntries = true
    ): array {
        // A plain list is not a patch {action, data} and replaces the field as it is.
        if (array_is_list($fieldData)) {
            return $fieldData;
        }

        $newData = $fieldData[PatchDataKeys::DATA->value] ?? null;
        $newData = is_array($newData) ? $newData : [];
        if (!$allowRepeatedEntries) {
            $existingValues = $this->keepFirstEntryPerElement($existingValues, $dataKey);
        }

        return match ($fieldData[PatchDataKeys::ACTION->value] ?? null) {
            PatcherActions::ADD->value => $this->handleAddition(
                $existingValues,
                $newData,
                $dataKey,
                $allowRepeatedEntries
            ),
            PatcherActions::REMOVE->value => $this->handleRemoval($existingValues, $newData, $dataKey),
            default => $newData
        };
    }

    /**
     * @throws Exception
     */
    private function patchEditableData(Concrete $element, array $data, UserInterface $user): void
    {
        $class = $element->getClass();
        foreach ($data as $key => $value) {
            $fieldDefinition = $class->getFieldDefinition($key);
            if ($fieldDefinition === null || !array_key_exists($key, $data)) {
                continue;
            }

            $adapter = $this->dataAdapterService->tryDataAdapter($fieldDefinition->getFieldtype());
            if ($adapter === null) {
                continue;
            }

            $value = $adapter->getDataForSetter($element, $fieldDefinition, $key, $data, $user, isPatch: true);
            if (!$this->validateEncryptedField($fieldDefinition, $value)) {
                continue;
            }

            $element->setValue($key, $value);
        }
    }

    /**
     * Keeps all existing entries, including repeated assignments and their data, and appends the new entries whose
     * element is not in the existing entries, like AbstractRelations::appendData(). Elements are matched by the id
     * and type sent in the payload.
     */
    private function handleAddition(
        array $existingValues,
        array $newData,
        ?string $dataKey,
        bool $allowRepeatedEntries
    ): array {
        $result = array_values($existingValues);
        $existingKeys = $this->getFieldMapKeys($result, $dataKey);
        foreach ($newData as $newEntry) {
            $entryKey = $this->getFieldMapKey($newEntry, $dataKey);
            if ($entryKey !== null && isset($existingKeys[$entryKey])) {
                continue;
            }

            $result[] = $newEntry;
            if ($entryKey !== null && !$allowRepeatedEntries) {
                $existingKeys[$entryKey] = true;
            }
        }

        return $result;
    }

    /**
     * Removes every existing entry of the given elements, like AbstractRelations::removeData().
     */
    private function handleRemoval(array $existingValues, array $newData, ?string $dataKey = null): array
    {
        $removedKeys = $this->getFieldMapKeys($newData, $dataKey);

        return array_values(array_filter(
            $existingValues,
            function (array $existingItem) use ($removedKeys, $dataKey): bool {
                $entryKey = $this->getFieldMapKey($existingItem, $dataKey);

                return $entryKey === null || !isset($removedKeys[$entryKey]);
            }
        ));
    }

    /**
     * Drops repeated entries of an element, e.g. left over from before a field disallowed multiple assignments,
     * which AbstractRelations::performMultipleAssignmentCheck() would reject on save.
     */
    private function keepFirstEntryPerElement(array $entries, ?string $dataKey): array
    {
        $result = [];
        $seenKeys = [];
        foreach ($entries as $entry) {
            $entryKey = $this->getFieldMapKey($entry, $dataKey);
            if ($entryKey !== null && isset($seenKeys[$entryKey])) {
                continue;
            }

            $result[] = $entry;
            if ($entryKey !== null) {
                $seenKeys[$entryKey] = true;
            }
        }

        return $result;
    }

    /**
     * @return array<string, true>
     */
    private function getFieldMapKeys(array $items, ?string $dataKey = null): array
    {
        $keys = [];
        foreach ($items as $item) {
            $entryKey = $this->getFieldMapKey($item, $dataKey);
            if ($entryKey !== null) {
                $keys[$entryKey] = true;
            }
        }

        return $keys;
    }

    /**
     * Null for entries without an element, e.g. an image gallery item without an image: they never match.
     * The API type "data-object" is matched as the core type "object" that existing entries carry.
     */
    private function getFieldMapKey(array $item, ?string $dataKey = null): ?string
    {
        $elementData = $dataKey ? ($item[$dataKey] ?? null) : $item;
        if (!isset($elementData[FieldKeys::ID_KEY->value], $elementData[FieldKeys::TYPE_KEY->value])) {
            return null;
        }

        $type = $elementData[FieldKeys::TYPE_KEY->value];
        if ($type === ElementTypes::TYPE_DATA_OBJECT) {
            $type = ElementTypes::TYPE_OBJECT;
        }

        return $elementData[FieldKeys::ID_KEY->value] . '_' . $type;
    }

    private function patchAsynchronously(
        string $elementType,
        array $patchData,
        UserInterface $user,
    ): int {
        $job = new Job(
            name: Jobs::PATCH_ELEMENTS->value,
            steps: [
                new JobStep(JobSteps::ELEMENT_PATCHING->value, PatchMessage::class, '', []),
            ],
            selectedElements: array_map(
                static fn (array $data) => new ElementDescriptor(
                    $elementType,
                    $data['id']
                ),
                $patchData
            ),
            environmentData: array_column($patchData, null, 'id'),
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_CONTINUE_ON_ERROR->value
        );

        return $jobRun->getId();
    }
}
