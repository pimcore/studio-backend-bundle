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
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportAssetParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Util\Constants\Csv;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
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
    ) {
    }

    public function generateCsvFile(ExportAssetParameter $exportAssetParameter): int
    {
        $jobStepConfig = [
            Csv::JOB_STEP_CONFIG_SETTINGS->value => $exportAssetParameter->getSettings(),
            Csv::JOB_STEP_CONFIG_CONFIGURATION->value => $exportAssetParameter->getGridConfig(),
        ];

        $jobSteps = array_map(
            static fn (ElementDescriptor $asset) => new JobStep(
                JobSteps::CSV_CREATION->value,
                CsvCreationMessage::class,
                '',
                array_merge(
                    [
                        csv::ASSET_TO_EXPORT->value => $asset,
                    ],
                    $jobStepConfig
                )
            ),
            $exportAssetParameter->getAssets(),
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

    public function getCsvFile(int $id, Configuration $configuration, array $settings): string
    {
        $storage = $this->storageService->getTempStorage();
        $folderName = $this->getTempFileName($id, self::CSV_FOLDER_NAME);
        $file = $this->getTempFileName($id, self::CSV_FILE_NAME);

        try {
            $storage->createDirectory($folderName);
            $path = $folderName . '/' . $file;
            if (!$storage->fileExists($path)) {
                $headers = $this->getHeaders($configuration, $settings);
                $storage->write(
                    $path,
                    implode(
                        $settings[Csv::SETTINGS_DELIMITER->value] ?? ',',
                        $headers
                    ) . Csv::NEW_LINE->value
                );
            }

        } catch (FilesystemException $e) {
            throw new EnvironmentException('Could not create or read CSV file: ' . $e->getMessage());
        }

        return $path;
    }

    /**
     * @throws FilesystemException
     */
    public function addData(string $filePath, string $delimiter, array $data): void
    {
        $storage = $this->storageService->getTempStorage();
        $fileStream = $storage->readStream($filePath);

        $temp = tmpfile();
        stream_copy_to_stream($fileStream, $temp, null, 0);

        fwrite(
            $temp,
            implode($delimiter, array_map([$this, 'encodeFunc'], $data)) . Csv::NEW_LINE->value
        );

        $storage->writeStream($filePath, $temp);
    }

    private function encodeFunc(?string $value): string
    {
        $value = str_replace('"', '""', $value ?? '');

        //force wrap value in quotes and return
        return '"' . $value . '"';
    }

    private function getHeaders(Configuration $configuration, array $settings): array
    {
        $header = $settings[Csv::SETTINGS_HEADER->value] ?? Csv::SETTINGS_HEADER_NO_HEADER->value;
        if ($header === Csv::SETTINGS_HEADER_NO_HEADER->value) {
            return [];
        }

        return $this->gridService->getColumnKeys(
            $configuration,
            $header === Csv::SETTINGS_HEADER_NAME->value
        );
    }
}
