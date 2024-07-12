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

use Exception;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CollectionMessage;
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
        string $filePath = self::DOWNLOAD_ZIP_FILE_PATH,
        bool $create = true,
    ): ?ZipArchive {
        $zipPath = $this->getTempFilePath($id, $filePath);

        $archive = new ZipArchive();

        if (!file_exists($zipPath)) {
           $archive->open($zipPath, ZipArchive::CREATE);
           return $archive;
        }

        if ($archive->open($zipPath) === true) {
            return $archive;
        }

        return null;
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

    public function getArchiveFiles(
        ZipArchive $archive,
        string $targetPath
    ): array {
        $files = [];
        $fileCount = $archive->count();
        if (!$archive->extractTo($targetPath)) {
            throw new EnvironmentException('Failed to extract zip archive.');
        }

        foreach (range(0, $fileCount - 1) as $i) {
            $fileName = $this->uploadService->sanitizeFileToUpload($archive->getNameIndex($i));
            if ($fileName !== null) {
                $files[] = [
                    'fileName' => $fileName,
                    'sourcePath' => $targetPath . '/' . $fileName,
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
        $this->copyUploadZipFile($zipArchive->getRealPath(), $archiveId);
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
        $steps = [
            new JobStep(JobSteps::ZIP_COLLECTION->value, CollectionMessage::class, '', []),
            new JobStep(JobSteps::ZIP_CREATION->value, ZipCreationMessage::class, '', []),
        ];

        $job = new Job(
            name: Jobs::CREATE_ZIP->value,
            steps: $steps,
            selectedElements: $ids->getItems()
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
    public function cleanUpArchiveFolder(
        string $folder
    ): void {
        $storage = $this->storageService->getTempStorage();
        if (empty($storage->listContents($folder)->toArray())) {
            $storage->deleteDirectory($folder);
        }
    }

    public function cleanUpArchive(
        string $archive
    ): void {
        $this->storageService->removeTempFile($archive);
    }

    public function copyFileToTemp(int $jobRunId): void
    {
        $storage = $this->storageService->getTempStorage();

        $storage->writeStream(
            $this->getTempFileName($jobRunId, self::DOWNLOAD_ZIP_FILE_NAME),
            fopen($this->getTempFilePath($jobRunId, self::DOWNLOAD_ZIP_FILE_PATH), 'rb')
        );

    }

    private function copyUploadZipFile(
        string $zipArchivePath,
        string $archiveId
    ): void {
        if (!is_file($zipArchivePath)) {
            throw new EnvironmentException(
                'Something went wrong, please check upload_max_filesize and post_max_size in your php.ini ' .
                ' as well as the write permissions of your temporary directories.'
            );
        }

        $pathTarget = $this->getTempFilePath($archiveId, self::UPLOAD_ZIP_FILE_PATH);
        copy($zipArchivePath, $pathTarget);
    }
}
