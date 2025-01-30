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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Service;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\ExecutionEngineServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StreamedResponseTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\TempFilePathTrait;
use Symfony\Component\HttpFoundation\StreamedResponse;
use function sprintf;

/**
 * @internal
 */
final readonly class DownloadService implements DownloadServiceInterface
{
    use StreamedResponseTrait;
    use TempFilePathTrait;

    public function __construct(
        private ExecutionEngineServiceInterface $executionEngineService,
        private StorageServiceInterface $storageService
    ) {
    }

    /**
     * @throws EnvironmentException|ForbiddenException|NotFoundException|StreamResourceNotFoundException
     */
    public function downloadResourceByJobRunId(
        int $jobRunId,
        string $tempFileName,
        string $tempFolderName,
        string $mimeType,
        string $downloadName,
    ): StreamedResponse {
        $this->executionEngineService->validateJobRun($jobRunId);
        $fileName = $this->getTempFileName($jobRunId, $tempFileName);
        $folderName = $this->getTempFileName($jobRunId, $tempFolderName);
        $filePath = $folderName . '/' . $fileName;
        $storage = $this->validateStorage($filePath, $jobRunId);

        $streamedResponse = $this->getFileStreamedResponse(
            $filePath,
            $mimeType,
            $downloadName,
            $storage
        );

        try {
            $storage->delete($filePath);
            $this->storageService->cleanUpFolder($folderName);
        } catch (FilesystemException) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to clean up temporary folder %s',
                    $folderName
                )
            );
        }

        return $streamedResponse;
    }

    /**
     * @throws EnvironmentException|NotFoundException
     */
    public function cleanupDataByJobRunId(
        int $jobRunId,
        string $folderName,
        string $fileName
    ): void {
        $this->executionEngineService->validateJobRun($jobRunId);
        $this->validateStorage($this->getTempFilePath($jobRunId, $folderName . '/' . $fileName), $jobRunId);

        try {
            $this->storageService->cleanUpFolder(
                $this->getTempFileName(
                    $jobRunId,
                    $folderName
                ),
                true
            );
        } catch (FilesystemException $e) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to delete file based on jobRunId %d: %s',
                    $jobRunId,
                    $e->getMessage()
                ),
            );
        }
    }

    /**
     * @throws EnvironmentException
     */
    private function validateStorage(string $filePath, int $jobRunId): FilesystemOperator
    {
        $storage = $this->storageService->getTempStorage();
        if (!$this->storageService->tempFileExists($filePath)) {
            throw new EnvironmentException(
                sprintf(
                    'Resource not found for jobRun with Id %d',
                    $jobRunId
                )
            );
        }

        return $storage;
    }
}
