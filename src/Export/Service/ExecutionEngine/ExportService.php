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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Service\ExecutionEngine;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ExportDataCollectionMessage as AssetCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ExportFolderDataCollectionMessage as AssetFolderCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Messages\ExportDataCollectionMessage as DataObjectCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Messages\ExportFolderDataCollectionMessage as DataObjectFolderCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\XlsxCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Constant\ExportFormat;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementDescriptor;

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

    public function generateExportFileForElements(ExportParameter $exportParameter, string $exportFormat): int
    {
        $collectionSettings = [
            StepConfig::CONFIG_COLUMNS->value => $exportParameter->getColumns(),
            StepConfig::ELEMENT_TYPE->value => $exportParameter->getElementType(),
        ];

        $creationSettings = [
            StepConfig::CONFIG_COLUMNS->value => $exportParameter->getColumns(),
            StepConfig::CONFIG_CONFIGURATION->value => $exportParameter->getConfig(),
        ];

        return $this->generateExportFileJob(
            $exportParameter->getElements(),
            $collectionSettings,
            $creationSettings,
            $this->getMessageClass($exportParameter->getElementType()),
            $exportFormat
        );
    }

    public function generateExportFileForFolders(ExportFolderParameter $exportParameter, string $exportFormat): int
    {
        $collectionSettings = [
            StepConfig::CONFIG_COLUMNS->value => $exportParameter->getColumns(),
            StepConfig::CONFIG_FILTERS->value => $exportParameter->getFilters(),
        ];

        $creationSettings = [
            StepConfig::CONFIG_COLUMNS->value => $exportParameter->getColumns(),
            StepConfig::CONFIG_CONFIGURATION->value => $exportParameter->getConfig(),
        ];

        return $this->generateExportFileJob(
            $exportParameter->getFolders(),
            $collectionSettings,
            $creationSettings,
            $this->getMessageClassForFolder($exportParameter->getElementType()),
            $exportFormat
        );
    }

    private function generateExportFileJob(
        array $elements,
        array $collectionSettings,
        array $creationSettings,
        string $messageFQCN,
        string $exportFormat
    ): int {

        $jobSteps = [
            ...$this->mapJobSteps($elements, $collectionSettings, $messageFQCN, StepConfig::ELEMENT_TO_EXPORT),
            ...[$this->getExportFileStep($creationSettings, $exportFormat)],
        ];

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $this->createJobByFormat($jobSteps, $exportFormat),
            $this->securityService->getCurrentUser()->getId(),
            Config::CONTEXT_STOP_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    private function mapJobSteps(
        array $elements,
        array $collectionSettings,
        string $messageFQCN,
        StepConfig $export
    ): array {
        return array_map(
            static fn (ElementDescriptor $element) => new JobStep(
                JobSteps::DATA_COLLECTION->value,
                $messageFQCN,
                '',
                array_merge([$export->value => $element], $collectionSettings)
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

    private function createJobByFormat(array $jobSteps, string $exportFormat): Job
    {
        $name = Jobs::CREATE_CSV->value;
        if ($exportFormat === ExportFormat::XLSX->value) {
            $name = Jobs::CREATE_XLSX->value;
        }

        return new Job(
            name: $name,
            steps: $jobSteps
        );
    }

    private function getMessageClass(string $elementType): string
    {
        return match($elementType) {
            ElementTypes::TYPE_ASSET => AssetCollectionMessage::class,
            ElementTypes::TYPE_OBJECT => DataObjectCollectionMessage::class,
            default => throw new InvalidElementTypeException($elementType)
        };
    }

    private function getMessageClassForFolder(string $elementType): string
    {
        return match($elementType) {
            ElementTypes::TYPE_ASSET => AssetFolderCollectionMessage::class,
            ElementTypes::TYPE_OBJECT => DataObjectFolderCollectionMessage::class,
            default => throw new InvalidElementTypeException($elementType)
        };
    }
}
