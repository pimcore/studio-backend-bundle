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
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementIndexServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\PatchFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\UpdateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\DataObject\FieldKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatchDataKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatcherActions;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version\CoauthorContextInterface;
use function array_key_exists;
use function count;
use function is_string;
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
        private CoauthorContextInterface $coauthorContext,
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
     * @throws ElementSavingFailedException
     */
    public function patchElement(
        ElementInterface $element,
        string $elementType,
        array $elementPatchData,
        UserInterface $user,
    ): void {
        $coauthorSnapshot = $this->activateCoauthorContext($elementPatchData);

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

            $this->elementSaveService->save(
                $element,
                $user,
                $elementPatchData[ElementSaveServiceInterface::INDEX_TASK] ?? null
            );

            if (isset($elementPatchData['index'])) {
                $this->indexService->indexRelatedElements($element, $elementPatchData['index']);
            }
        } catch (DuplicateFullPathException) {
            throw new ElementExistsException(
                message: sprintf('Element with full path [%s] already exists', $element->getRealFullPath())
            );
        } catch (Exception $exception) {
            throw new ElementSavingFailedException($element->getId(), $exception->getMessage());
        } finally {
            $this->restoreCoauthorContext($coauthorSnapshot);
        }
    }

    /**
     * @return array{type: ?string, coauthor: ?string}|null Snapshot of the previous context,
     *                                                      or null if nothing was activated
     */
    private function activateCoauthorContext(array $elementPatchData): ?array
    {
        $coauthorType = $elementPatchData[ElementSaveServiceInterface::INDEX_COAUTHOR_TYPE] ?? null;
        $coauthor = $elementPatchData[ElementSaveServiceInterface::INDEX_COAUTHOR] ?? null;

        if (!is_string($coauthorType) || $coauthorType === '' || !is_string($coauthor) || $coauthor === '') {
            return null;
        }

        $previous = [
            'type' => $this->coauthorContext->getType(),
            'coauthor' => $this->coauthorContext->getCoauthor(),
        ];
        $this->coauthorContext->set($coauthorType, $coauthor);

        return $previous;
    }

    private function restoreCoauthorContext(?array $previous): void
    {
        if ($previous === null) {
            return;
        }

        if ($previous['type'] !== null && $previous['coauthor'] !== null) {
            $this->coauthorContext->set($previous['type'], $previous['coauthor']);

            return;
        }

        $this->coauthorContext->clear();
    }

    public function handlePatchDataField(array $fieldData, array $existingValues, ?string $dataKey = null): array
    {
        $newData = $fieldData[PatchDataKeys::DATA->value];
        $action = $fieldData[PatchDataKeys::ACTION->value];

        $existingMap = [];
        foreach ($existingValues as $existingItem) {
            $existingMap[$this->getFieldMapKey($existingItem, $dataKey)] = $existingItem;
        }

        return match ($action) {
            PatcherActions::ADD->value => $this->handleAddition($existingMap, $newData, $dataKey),
            PatcherActions::REMOVE->value => $this->handleRemoval($existingMap, $newData, $dataKey),
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

    private function handleAddition(array $existingMap, array $newData, ?string $dataKey = null): array
    {
        foreach ($newData as $newEntry) {
            $existingMap[$this->getFieldMapKey($newEntry, $dataKey)] = $newEntry;
        }

        return array_values($existingMap);
    }

    private function handleRemoval(array $existingMap, array $newData, ?string $dataKey = null): array
    {
        foreach ($newData as $newEntry) {
            $entryKey = $this->getFieldMapKey($newEntry, $dataKey);
            if (isset($existingMap[$entryKey])) {
                unset($existingMap[$entryKey]);
            }
        }

        return array_values($existingMap);
    }

    private function getFieldMapKey(array $item, ?string $dataKey = null): string
    {
        $elementData = $dataKey ? $item[$dataKey] : $item;

        return $elementData[FieldKeys::ID_KEY->value] . '_' . $elementData[FieldKeys::TYPE_KEY->value];
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
