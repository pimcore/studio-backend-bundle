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

namespace Pimcore\Bundle\StudioBackendBundle\Patcher\Service;

use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\PatchFolderMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\PatchMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\PatchFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\UpdateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\DataObject\FieldKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatchDataKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PatcherActions;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function array_key_exists;
use function count;

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
        private ElementSaveServiceInterface $elementSaveService
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

    public function patchFolder(
        string $elementType,
        PatchFolderParameter $patchFolderParameter,
        UserInterface $user,
    ): ?int {
        $job = new Job(
            name: Jobs::PATCH_ELEMENTS->value,
            steps: [
                new JobStep(
                    JobSteps::ELEMENT_FOLDER_PATCHING->value,
                    PatchFolderMessage::class,
                    '',
                    ['filters' => $patchFolderParameter->getFilters()]
                ),
            ],
            selectedElements: array_map(
                static fn (array $data) => new ElementDescriptor(
                    $elementType,
                    $data['folderId']
                ),
                $patchFolderParameter->getData()
            ),
            environmentData: array_column($patchFolderParameter->getData(), null, 'folderId'),
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
        } catch (Exception $exception) {
            throw new ElementSavingFailedException($element->getId(), $exception->getMessage());
        }
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
            default => $fieldData
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
