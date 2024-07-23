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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipCopyMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipUploadMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\CreateAssetFileParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\TempFilePathTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

/**
 * @internal
 */
final readonly class ZipService implements ZipServiceInterface
{
    use TempFilePathTrait;

    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
        private StorageServiceInterface $storageService,
        private UploadServiceInterface $uploadService,
    ) {
    }

    public function getZipArchive(
        mixed $id,
        string $fileName = self::DOWNLOAD_ZIP_FILE_NAME,
        bool $create = true
    ): ?ZipArchive {
        $zip = $this->getTempFileName($id, $fileName);
        $zipStoragePath = $this->getTempFilePathFromName($id, $fileName);
        $archive = new ZipArchive();

        $state = false;

        try {
            if ($this->storageService->getTempStorage()->fileExists($zip)) {
                $state = $archive->open($zipStoragePath);
            }

            if (!$state && $create) {
                $state = $archive->open($zipStoragePath, ZipArchive::CREATE);
            }
        } catch (FilesystemException) {
            return null;
        }

        if ($state !== true) {
            return null;
        }

        return $archive;
    }

    public function addFile(ZipArchive $archive, Asset $asset): void
    {
        $archive->addFile(
            $asset->getLocalFile(),
            preg_replace(
                '@^' . preg_quote($asset->getRealPath(), '@') . '@i',
                '',
                $asset->getRealFullPath()
            )
        );
    }

    public function extractArchiveFiles(
        ZipArchive $archive,
        string $targetPath
    ): array {
        $files = [];
        $fileCount = $archive->count();
        if (!$archive->extractTo($targetPath)) {
            throw new EnvironmentException('Failed to extract zip archive.');
        }

        foreach (range(0, $fileCount - 1) as $i) {
            $path = $this->uploadService->sanitizeFileToUpload($archive->getNameIndex($i));
            if ($path !== null) {
                $sourcePath = $targetPath . '/' . $path;
                $files[] = [
                    'name' => basename($path),
                    'path' => $path,
                    'sourcePath' => $sourcePath,
                    'type' => is_dir($sourcePath) ? ElementTypes::TYPE_FOLDER : ElementTypes::TYPE_ASSET,
                ];
            }
        }

        return $files;
    }

    /**
     * @throws AccessDeniedException|EnvironmentException|ForbiddenException|NotFoundException
     */
    public function uploadZipAssets(
        UserInterface $user,
        UploadedFile $zipArchive,
        int $parentId
    ): int {
        $this->uploadService->validateParent($user, $parentId);
        $archiveId = $parentId . '-' . time();
        $this->copyZipFileToFlysystem(
            $archiveId,
            self::UPLOAD_ZIP_FOLDER_NAME,
            self::UPLOAD_ZIP_FILE_NAME,
            $zipArchive->getRealPath(),
        );

        $job = new Job(
            name: Jobs::ZIP_FILE_UPLOAD->value,
            steps: [
                new JobStep(JobSteps::ZIP_UPLOADING->value, ZipUploadMessage::class, '', []),
            ],
            selectedElements: [new ElementDescriptor(
                $archiveId,
                $parentId
            )],
            environmentData: [
                EnvironmentVariables::PARENT_ID->value => $parentId,
            ]
        );
        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_CONTINUE_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    public function generateZipFile(CreateAssetFileParameter $ids): int
    {
        $jobSteps = array_map(
            static fn (int $id) => new JobStep(
                JobSteps::ZIP_CREATION->value,
                ZipCreationMessage::class,
                '',
                [self::ASSET_TO_ZIP => $id]
            ),
            $ids->getItems()
        );

        $jobSteps[] = new JobStep(
            JobSteps::ZIP_COPY->value,
            ZipCopyMessage::class,
            '',
            []
        );

        $job = new Job(
            name: Jobs::CREATE_ZIP->value,
            steps: $jobSteps,
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $this->securityService->getCurrentUser()->getId(),
            Config::CONTEXT_STOP_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    /**
     * @throws EnvironmentException
     */
    public function copyZipFileToFlysystem(
        string $id,
        string $folderName,
        string $archiveName,
        string $localPath
    ): void {
        $storage = $this->storageService->getTempStorage();
        $archiveFileName = $this->getTempFileName($id, $archiveName);
        if (!is_file($localPath)) {
            throw new EnvironmentException(
                sprintf(
                    'The zip archive %s could not be found at %s.',
                    $archiveFileName,
                    $localPath
                )
            );
        }

        try {
            $folderName = $this->getTempFilePath($id, $folderName);
            $storage->createDirectory($folderName);
            $storage->writeStream(
                $folderName . '/' . $archiveFileName,
                fopen($localPath, 'rb')
            );
            @unlink($localPath);
        } catch (FilesystemException) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to copy zip archive %s to Flysystem.',
                    $archiveFileName
                )
            );
        }
    }

    /**
     * @throws EnvironmentException
     */
    public function downloadZipFileFromFlysystem(
        string $id,
        string $folderName,
        string $archiveName,
        string $localPath
    ): ZipArchive {
        $storage = $this->storageService->getTempStorage();
        $archiveFileName = $this->getTempFileName($id, $archiveName);

        try {
            $folderName = $this->getTempFileName($id, $folderName);
            $stream = $storage->readStream($folderName . '/' . $archiveFileName);
            $localArchive = fopen($localPath, 'wb');
            stream_copy_to_stream($stream, $localArchive);
            fclose($stream);
            fclose($localArchive);
            $storage->delete($folderName . '/' . $archiveFileName);
        } catch (FilesystemException) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to get zip archive %s from Flysystem.',
                    $archiveFileName
                )
            );
        }

        return $this->createLocalArchive($archiveFileName, $localPath);
    }

    /**
     * @throws EnvironmentException
     */
    private function createLocalArchive(
        string $archiveName,
        string $localPath
    ): ZipArchive {
        $archive = new ZipArchive();
        $state = $archive->open($localPath);
        if ($state !== true) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to get zip archive %s locally.',
                    $archiveName
                )
            );
        }

        return $archive;
    }
}
