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
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\FolderCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\XlsxCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Constant\ExportFormat;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class ExportService implements ExportServiceInterface
{
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
    ): int
    {
        $elementType = $exportParameter->getElementType();
        $classId = $exportParameter->getClassId();
        if ($elementType === ElementTypes::TYPE_OBJECT && empty($classId)) {
            throw new InvalidArgumentException('Class ID must be provided for object folder patching');
        }

        $collectionSettings = [
            StepConfig::ELEMENT_CLASS_ID->value => $classId ?? '',
            StepConfig::CONFIG_COLUMNS->value => $exportParameter->getColumns(),
            StepConfig::ELEMENT_TYPE->value => $exportParameter->getElementType(),
        ];

        $creationSettings = [
            StepConfig::CONFIG_COLUMNS->value => $exportParameter->getColumns(),
            StepConfig::CONFIG_CONFIGURATION->value => $exportParameter->getConfig(),
        ];

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

        return $this->generateExportFileJob($jobSteps, $exportFormat, $user->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function generateExportFileForFolders(
        int $folderId,
        ExportFolderParameter $exportParameter,
        string $exportFormat
    ): int
    {
        $elementType = $exportParameter->getElementType();
        $classId = $exportParameter->getClassId();
        if ($elementType === ElementTypes::TYPE_OBJECT && empty($classId)) {
            throw new InvalidArgumentException('Class ID must be provided for object folder patching');
        }

        return $this->generateExportFileJob(
            [
                new JobStep(
                    JobSteps::DATA_COLLECTION->value,
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
                )
            ],
            $exportFormat,
            $this->securityService->getCurrentUser()->getId(),
            [new ElementDescriptor($elementType, $folderId)]
        );
    }

    private function generateExportFileJob(
        array $jobSteps,
        string $exportFormat,
        int $ownerId,
        array $selectedElements = [],
    ): int {
        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $this->createJobByFormat($jobSteps, $exportFormat, $selectedElements),
            $ownerId,
            Config::CONTEXT_STOP_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    private function mapJobSteps(
        array $elements,
        array $collectionSettings,
        string $messageFQCN,
    ): array {
        return array_map(
            static fn (ElementDescriptor $element) => new JobStep(
                JobSteps::DATA_COLLECTION->value,
                $messageFQCN,
                '',
                array_merge([StepConfig::ELEMENT_TO_EXPORT->value => $element], $collectionSettings)
            ),
            $elements,
        );
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

    private function createJobByFormat(array $jobSteps, string $exportFormat, array $selectedElements = []): Job
    {
        $name = Jobs::CREATE_CSV->value;
        if ($exportFormat === ExportFormat::XLSX->value) {
            $name = Jobs::CREATE_XLSX->value;
        }

        return new Job($name, $jobSteps, $selectedElements);
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
