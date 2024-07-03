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
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\StorageResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportAssetParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\TempFilePathTrait;

/**
 * @internal
 */
final class CsvService implements CsvServiceInterface
{
    use TempFilePathTrait;

    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
        private StorageResolverInterface $storageResolver,
        private GridServiceInterface $gridService,
    ) {
    }

    public function generateCsvFile(ExportAssetParameter $exportAssetParameter): string
    {
        $steps = [
            new JobStep(JobSteps::CSV_CREATION->value, CollectionMessage::class, '', []),
            new JobStep(
                JobSteps::CSV_CREATION->value,
                CsvCreationMessage::class,
                '',
                [
                    'settings' => $exportAssetParameter->getSettings(),
                    'configuration' => $exportAssetParameter->getGridConfig(),
                ]
            ),
        ];

        $job = new Job(
            name: Jobs::CREATE_CSV->value,
            steps: $steps,
            selectedElements: $exportAssetParameter->getAssets(),
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $this->securityService->getCurrentUser()->getId(),
            Config::CONTEXT_STOP_ON_ERROR->value
        );

        return $this->getTempFilePath($jobRun->getId(), self::CSV_FILE_PATH);
    }

    public function getCsvFile(int $id, Configuration $configuration, array $settings): string
    {
        $storage = $this->storageResolver->get('temp');
        $file = 'download-csv-'.$id.'.csv';

        try {
            if (!$storage->fileExists($file)) {
                $headers = $this->gridService->getColumnKeys($configuration);
                $storage->write($file, implode($settings['delimiter'] ?? ',', $headers). "\r\n");
            }

        } catch (FilesystemException $e) {
            throw new EnvironmentException('Could not create or read CSV file: ' . $e->getMessage());
        }

        return $file;
    }

    public function addData(string $filePath, string $delimiter, array $data): void
    {
        $storage = $this->storageResolver->get('temp');
        $fileStream = $storage->readStream($filePath);

        $temp = tmpfile();
        stream_copy_to_stream($fileStream, $temp, null, 0);

        fwrite($temp, implode($delimiter, array_map([$this, 'encodeFunc'], $data)) . "\r\n");

        $storage->writeStream($filePath, $temp);
    }

    private function encodeFunc(?string $value): string
    {
        $value = str_replace('"', '""', $value ?? '');

        //force wrap value in quotes and return
        return '"' . $value . '"';
    }
}