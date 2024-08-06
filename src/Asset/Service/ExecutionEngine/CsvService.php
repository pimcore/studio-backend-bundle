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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportAssetParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Util\Constants\Csv;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\TempFilePathTrait;
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

    public function generateCsvFile(ExportAssetParameter $exportAssetParameter): int
    {
        $jobStepConfigConfiguration = [
            Csv::JOB_STEP_CONFIG_CONFIGURATION->value => $exportAssetParameter->getGridConfig(),
        ];
        $jobStepConfigSettings = [
            Csv::JOB_STEP_CONFIG_SETTINGS->value => $exportAssetParameter->getSettings(),
        ];

        $jobSteps = array_map(
            static fn (ElementDescriptor $asset) => new JobStep(
                JobSteps::CSV_COLLECTION->value,
                CsvCollectionMessage::class,
                '',
                array_merge([csv::ASSET_TO_EXPORT->value => $asset], $jobStepConfigConfiguration)
            ),
            $exportAssetParameter->getAssets(),
        );

        $jobSteps[] = new JobStep(
            JobSteps::CSV_CREATION->value,
            CsvCreationMessage::class,
            '',
            array_merge($jobStepConfigSettings, $jobStepConfigConfiguration)
        );

        $job = new Job(
            name: Jobs::CREATE_CSV->value,
            steps: $jobSteps
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $this->securityService->getCurrentUser()->getId(),
            Config::CONTEXT_STOP_ON_ERROR->value
        );

        return $jobRun->getId();
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
}
