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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Service\ExecutionEngine;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ExportDataCollectionMessage as AssetCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Messages\ExportDataCollectionMessage as DataObjectCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\ChunkGeneratorTrait;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\FolderCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\XlsxCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Constant\ExportFormat;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\UserInterface;
use function basename;
use function is_string;
use function trim;

/**
 * @internal
 */
final readonly class ExportService implements ExportServiceInterface
{
    use ChunkGeneratorTrait;

    private const int EXPORT_BATCH_SIZE = 500;

    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function generateExportFileForElements(
        ExportParameter $exportParameter,
        string $exportFormat,
        ?UserInterface $user = null,
    ): int {
        $elementType = $exportParameter->getElementType();
        $classId = $exportParameter->getClassId();
        if ($elementType === ElementTypes::TYPE_OBJECT && empty($classId)) {
            throw new InvalidArgumentException('Class ID must be provided for object folder patching');
        }

        $collectionSettings = [
            StepConfig::ELEMENT_CLASS_ID->value => $classId ?? '',
            StepConfig::CONFIG_COLUMNS->value => $exportParameter->getColumns(),
        ];

        $creationSettings = array_merge(
            $collectionSettings,
            [
                StepConfig::CONFIG_CONFIGURATION->value => $exportParameter->getConfig(),
                StepConfig::ELEMENT_TYPE->value => $exportParameter->getElementType(),
            ]
        );

        $jobSteps = [
            ...$this->mapJobSteps(
                $exportParameter->getElements(),
                $collectionSettings,
                $this->getMessageClass($exportParameter->getElementType())
            ),
            ...[$this->getExportFileStep($creationSettings, $exportFormat)],
        ];

        if ($user === null) {
            $user = $this->securityService->getCurrentUser();
        }

        return $this->generateExportFileJob(
            $jobSteps,
            $exportFormat,
            $user->getId(),
            downloadFileName: $this->getDownloadFileName($exportParameter->getConfig())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function generateExportFileForFolders(
        int $folderId,
        ExportFolderParameter $exportParameter,
        string $exportFormat
    ): int {
        $elementType = $exportParameter->getElementType();
        $classId = $exportParameter->getClassId();
        if ($elementType === ElementTypes::TYPE_OBJECT && empty($classId)) {
            throw new InvalidArgumentException('Class ID must be provided for object folder patching');
        }

        return $this->generateExportFileJob(
            [
                new JobStep(
                    JobSteps::FOLDER_DATA_COLLECTION->value,
                    FolderCollectionMessage::class,
                    '',
                    [
                        StepConfig::ELEMENT_CLASS_ID->value => $classId ?? '',
                        StepConfig::CONFIG_COLUMNS->value => $exportParameter->getColumns(),
                        StepConfig::CONFIG_CONFIGURATION->value => $exportParameter->getConfig(),
                        StepConfig::CONFIG_FILTERS->value => $exportParameter->getFilters(),
                        StepConfig::EXPORT_FORMAT->value => $exportFormat,
                        StepConfig::ELEMENT_TYPE->value => $elementType,
                    ]
                ),
            ],
            $exportFormat,
            $this->securityService->getCurrentUser()->getId(),
            [new ElementDescriptor($elementType, $folderId)],
            true,
            $this->getDownloadFileName($exportParameter->getConfig())
        );
    }

    private function generateExportFileJob(
        array $jobSteps,
        string $exportFormat,
        int $ownerId,
        array $selectedElements = [],
        bool $isFolder = false,
        ?string $downloadFileName = null
    ): int {
        $name = $this->createJobNameByFormat($exportFormat);
        if ($isFolder) {
            $name = $exportFormat === ExportFormat::XLSX->value
                ? Jobs::COLLECT_XLSX_FOLDER_EXPORT_ELEMENTS->value
                : Jobs::COLLECT_CSV_FOLDER_EXPORT_ELEMENTS->value;
        }

        $environmentData = [];
        if ($downloadFileName !== null) {
            $environmentData[DownloadServiceInterface::EXPORT_DOWNLOAD_FILENAME] = $downloadFileName;
        }

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            new Job($name, $jobSteps, $selectedElements, $environmentData),
            $ownerId,
            Config::CONTEXT_STOP_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    private function getDownloadFileName(array $config): ?string
    {
        $fileName = $config[StepConfig::SETTINGS_FILE_NAME->value] ?? null;
        if (!is_string($fileName) || trim($fileName) === '') {
            return null;
        }

        $fileName = preg_replace('/[^\w\-. ]/', '_', basename(trim($fileName)));

        return $fileName === '' ? null : $fileName;
    }

    private function mapJobSteps(
        array $elements,
        array $collectionSettings,
        string $messageFQCN,
    ): array {
        $steps = [];

        foreach ($this->chunkGenerator($elements, self::EXPORT_BATCH_SIZE) as $batch) {

            $config = [
                    StepConfig::ELEMENTS_TO_EXPORT->value => $batch,
                ] + $collectionSettings;

            $steps[] = new JobStep(
                JobSteps::DATA_COLLECTION->value,
                $messageFQCN,
                '',
                $config
            );
        }

        return $steps;
    }

    private function getExportFileStep(array $settings, string $exportFormat): JobStep
    {
        if ($exportFormat === ExportFormat::XLSX->value) {
            return $this->getXlsxCreationStep($settings);
        }

        return $this->getCsvCreationStep($settings);
    }

    private function getCsvCreationStep(array $settings): JobStep
    {
        return new JobStep(
            JobSteps::CSV_CREATION->value,
            CsvCreationMessage::class,
            '',
            $settings
        );
    }

    private function getXlsxCreationStep(array $settings): JobStep
    {
        return new JobStep(
            JobSteps::XLSX_CREATION->value,
            XlsxCreationMessage::class,
            '',
            $settings
        );
    }

    private function createJobNameByFormat(string $exportFormat): string
    {
        $name = Jobs::CREATE_CSV->value;
        if ($exportFormat === ExportFormat::XLSX->value) {
            $name = Jobs::CREATE_XLSX->value;
        }

        return $name;
    }

    private function getMessageClass(string $elementType): string
    {
        return match($elementType) {
            ElementTypes::TYPE_ASSET => AssetCollectionMessage::class,
            ElementTypes::TYPE_OBJECT => DataObjectCollectionMessage::class,
            default => throw new InvalidElementTypeException($elementType)
        };
    }
}
