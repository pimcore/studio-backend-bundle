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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Service;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_THROW_ON_ERROR;
use JsonException;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Constant\ExportFile;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\TempFilePathTrait;
use Psr\Log\LoggerInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class ExportDataStorageService implements ExportDataStorageServiceInterface
{
    use TempFilePathTrait;

    public function __construct(
        private StorageServiceInterface $storageService,
        private LoggerInterface $logger,
    ) {
    }

    public function store(int $jobRunId, int $batch, array $rows): void
    {
        $folder = $this->getFolder($jobRunId);

        try {
            $storage = $this->storageService->getTempStorage();
            $storage->createDirectory($folder);
            $storage->write(
                $folder . '/' . sprintf(ExportFile::DATA_BATCH_FILE_NAME->value, $batch),
                json_encode(array_values($rows), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION)
            );
        } catch (FilesystemException|JsonException $e) {
            throw new EnvironmentException(
                sprintf('Export data of batch %d could not be stored: %s', $batch, $e->getMessage())
            );
        }
    }

    public function load(int $jobRunId): ?array
    {
        $folder = $this->getFolder($jobRunId);

        try {
            $storage = $this->storageService->getTempStorage();
            $files = $storage->listContents($folder)
                ->filter(static fn (StorageAttributes $attributes): bool => $attributes->isFile())
                ->map(static fn (StorageAttributes $attributes): string => $attributes->path())
                ->toArray();
            if ($files === []) {
                return null;
            }

            // Batch file names are zero padded, so the alphabetical order is the order of the batches
            sort($files);

            $rows = [];
            foreach ($files as $file) {
                foreach (json_decode($storage->read($file), true, 512, JSON_THROW_ON_ERROR) as $row) {
                    $rows[] = $row;
                }
            }

            return $rows;
        } catch (FilesystemException|JsonException $e) {
            throw new EnvironmentException(
                sprintf('Export data of job run %d could not be read: %s', $jobRunId, $e->getMessage())
            );
        }
    }

    public function cleanUp(int $jobRunId): void
    {
        try {
            $this->storageService->cleanUpFolder($this->getFolder($jobRunId), true);
        } catch (FilesystemException $e) {
            $this->logger->error(
                'Failed to clean up the export data of job run {jobRunId}',
                [
                    'jobRunId' => $jobRunId,
                    'exception' => $e,
                ]
            );
        }
    }

    private function getFolder(int $jobRunId): string
    {
        return $this->getTempFileName($jobRunId, ExportFile::DATA_FOLDER_NAME->value);
    }
}
