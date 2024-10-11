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

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvAssetCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvFolderCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportAssetParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Util\Constant\Csv;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\TempFilePathTrait;
use Pimcore\Model\Element\ElementDescriptor;

/**
 * @internal
 */
final readonly class CsvService implements CsvServiceInterface
{
    use TempFilePathTrait;

    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
        private StorageServiceInterface $storageService,
        private GridServiceInterface $gridService,
        private string $defaultDelimiter,
    ) {
    }

    public function generateCsvFileForAssets(ExportAssetParameter $exportAssetParameter): int
    {
        $collectionSettings = [
            Csv::JOB_STEP_CONFIG_COLUMNS->value => $exportAssetParameter->getColumns(),
        ];

        $creationSettings = [
            Csv::JOB_STEP_CONFIG_COLUMNS->value => $exportAssetParameter->getColumns(),
            Csv::JOB_STEP_CONFIG_CONFIGURATION->value => $exportAssetParameter->getConfig(),
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
            Csv::JOB_STEP_CONFIG_COLUMNS->value => $exportFolderParameter->getColumns(),
            Csv::JOB_STEP_CONFIG_FILTERS->value => $exportFolderParameter->getFilters(),
        ];

        $creationSettings = [
            Csv::JOB_STEP_CONFIG_COLUMNS->value => $exportFolderParameter->getColumns(),
            Csv::JOB_STEP_CONFIG_CONFIGURATION->value => $exportFolderParameter->getConfig(),
        ];


        return $this->generateCsvFileJob(
            $exportFolderParameter->getFolders(),
            $collectionSettings,
            $creationSettings,
            CsvFolderCollectionMessage::class,
            Csv::FOLDER_TO_EXPORT
        );
    }

    /**
     * @throws FilesystemException
     */
    public function createCsvFile(
        int $id,
        ColumnCollection $columnCollection,
        array $settings,
        array $assetData,
        ?string $delimiter = null,
    ): void {
        $storage = $this->storageService->getTempStorage();
        $headers = $this->getHeaders($columnCollection, $settings);
        if ($delimiter === null) {
            $delimiter = $this->defaultDelimiter;
        }
        $data[] = implode($delimiter, $headers) . Csv::NEW_LINE->value;
        foreach ($assetData as $row) {
            $data[] = implode($delimiter, array_map([$this, 'encodeFunc'], $row)) . Csv::NEW_LINE->value;
        }

        $storage->write(
            $this->getCsvFilePath($id, $storage),
            implode($data)
        );
    }

    private function generateCsvFileJob(
        array $elements,
        array $collectionSettings,
        array $creationSettings,
        string $messageFQCN,
        Csv $export = Csv::ASSET_TO_EXPORT
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

    /**
     * @throws FilesystemException
     */
    private function getCsvFilePath(int $id, FilesystemOperator $storage): string
    {
        $folderName = $this->getTempFileName($id, self::CSV_FOLDER_NAME);
        $file = $this->getTempFileName($id, self::CSV_FILE_NAME);
        $storage->createDirectory($folderName);

        return $folderName . '/' . $file;
    }

    private function encodeFunc(?string $value): string
    {
        $value = str_replace('"', '""', $value ?? '');

        //force wrap value in quotes and return
        return '"' . $value . '"';
    }

    private function getHeaders(ColumnCollection $columnCollection, array $settings): array
    {
        $header = $settings[Csv::SETTINGS_HEADER->value] ?? Csv::SETTINGS_HEADER_NO_HEADER->value;
        if ($header === Csv::SETTINGS_HEADER_NO_HEADER->value) {
            return [];
        }

        return $this->gridService->getColumnKeys(
            $columnCollection,
            $header === Csv::SETTINGS_HEADER_NAME->value
        );
    }

    private function mapJobSteps(
        array $elements,
        array $collectionSettings,
        string $messageFQCN,
        Csv $export
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
            JobSteps::CSV_CREATION->value,
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
