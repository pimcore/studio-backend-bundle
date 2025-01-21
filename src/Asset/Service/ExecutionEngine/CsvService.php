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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvFolderCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportAssetParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\Util\JobSteps as ExportJobSteps;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
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

    public function generateCsvFileForAssets(ExportAssetParameter $exportAssetParameter): int
    {
        $collectionSettings = [
            StepConfig::CONFIG_COLUMNS->value => $exportAssetParameter->getColumns(),
        ];

        $creationSettings = [
            StepConfig::CONFIG_COLUMNS->value => $exportAssetParameter->getColumns(),
            StepConfig::CONFIG_CONFIGURATION->value => $exportAssetParameter->getConfig(),
        ];

        return $this->generateCsvFileJob(
            $exportAssetParameter->getAssets(),
            $collectionSettings,
            $creationSettings,
            CsvAssetCollectionMessage::class
        );
    }

    public function generateCsvFileForFolders(ExportFolderParameter $exportFolderParameter): int
    {
        $collectionSettings = [
            StepConfig::CONFIG_COLUMNS->value => $exportFolderParameter->getColumns(),
            StepConfig::CONFIG_FILTERS->value => $exportFolderParameter->getFilters(),
        ];

        $creationSettings = [
            StepConfig::CONFIG_COLUMNS->value => $exportFolderParameter->getColumns(),
            StepConfig::CONFIG_CONFIGURATION->value => $exportFolderParameter->getConfig(),
        ];

        return $this->generateCsvFileJob(
            $exportFolderParameter->getFolders(),
            $collectionSettings,
            $creationSettings,
            CsvFolderCollectionMessage::class,
            StepConfig::FOLDER_TO_EXPORT
        );
    }

    private function generateCsvFileJob(
        array $elements,
        array $collectionSettings,
        array $creationSettings,
        string $messageFQCN,
        StepConfig $export = StepConfig::ASSET_TO_EXPORT
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
}
