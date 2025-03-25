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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvAssetCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvAssetFolderCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Messages\CsvDataObjectCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Messages\CsvDataObjectFolderCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\Util\JobSteps as ExportJobSteps;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementDescriptor;

/**
 * @internal
 */
final readonly class CsvService implements CsvServiceInterface
{
    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService
    ) {
    }

    public function generateCsvFileForElements(ExportParameter $exportParameter): int
    {
        $collectionSettings = [
            StepConfig::CONFIG_COLUMNS->value => $exportParameter->getColumns(),
        ];

        $creationSettings = [
            StepConfig::CONFIG_COLUMNS->value => $exportParameter->getColumns(),
            StepConfig::CONFIG_CONFIGURATION->value => $exportParameter->getConfig(),
        ];

        return $this->generateCsvFileJob(
            $exportParameter->getElements(),
            $collectionSettings,
            $creationSettings,
            $this->getMessageClass($exportParameter->getElementType())
        );
    }

    public function generateCsvFileForFolders(ExportParameter $exportParameter): int
    {
        $collectionSettings = [
            StepConfig::CONFIG_COLUMNS->value => $exportParameter->getColumns(),
            StepConfig::CONFIG_FILTERS->value => $exportParameter->getFilters(),
        ];

        $creationSettings = [
            StepConfig::CONFIG_COLUMNS->value => $exportParameter->getColumns(),
            StepConfig::CONFIG_CONFIGURATION->value => $exportParameter->getConfig(),
        ];

        return $this->generateCsvFileJob(
            $exportParameter->getElements(),
            $collectionSettings,
            $creationSettings,
            $this->getMessageClassForFolder($exportParameter->getElementType()),
            StepConfig::FOLDER_TO_EXPORT
        );
    }

    private function generateCsvFileJob(
        array $elements,
        array $collectionSettings,
        array $creationSettings,
        string $messageFQCN,
        StepConfig $export = StepConfig::ELEMENT_TO_EXPORT
    ): int {

        $jobSteps = [
            ...$this->mapJobSteps($elements, $collectionSettings, $messageFQCN, $export),
            ...[$this->getCsvCreationStep($creationSettings)],
        ];

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $this->createJob($jobSteps),
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
            static fn (ElementDescriptor $asset) => new JobStep(
                JobSteps::CSV_COLLECTION->value,
                $messageFQCN,
                '',
                array_merge([$export->value => $asset], $collectionSettings)
            ),
            $elements,
        );
    }

    private function getCsvCreationStep(array $settings): JobStep
    {
        return new JobStep(
            ExportJobSteps::CSV_CREATION->value,
            CsvCreationMessage::class,
            '',
            $settings
        );
    }

    private function createJob(array $jobSteps): Job
    {
        return new Job(
            name: Jobs::CREATE_CSV->value,
            steps: $jobSteps
        );
    }

    private function getMessageClass(string $elementType): string
    {
        return match($elementType) {
            ElementTypes::TYPE_ASSET => CsvAssetCollectionMessage::class,
            ElementTypes::TYPE_OBJECT => CsvDataObjectCollectionMessage::class,
            default => throw new InvalidElementTypeException($elementType)
        };
    }

    private function getMessageClassForFolder(string $elementType): string
    {
        return match($elementType) {
            ElementTypes::TYPE_ASSET => CsvAssetFolderCollectionMessage::class,
            ElementTypes::TYPE_OBJECT => CsvDataObjectFolderCollectionMessage::class,
            default => throw new InvalidElementTypeException($elementType)
        };
    }
}
