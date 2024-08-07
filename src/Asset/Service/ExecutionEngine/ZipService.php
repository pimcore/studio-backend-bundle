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
use Pimcore\Bundle\GenericDataIndexBundle\Exception\AssetSearchException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipDownloadMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipUploadMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\CreateAssetFileParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\DownloadLimits;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\TempFilePathTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;
use function count;
use function sprintf;

/**
 * @internal
 */
final readonly class ZipService implements ZipServiceInterface
{
    use TempFilePathTrait;

    public function __construct(
        private AssetSearchServiceInterface $assetSearchService,
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
        private StorageServiceInterface $storageService,
        private UploadServiceInterface $uploadService,
        private array $downloadLimits,
    ) {
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

    /**
     * @throws EnvironmentException
     */
    public function generateZipFile(CreateAssetFileParameter $parameter): int
    {
        $items = $parameter->getItems();
        $this->validateDownloadItems($items);
        $job = new Job(
            name: Jobs::CREATE_ZIP->value,
            steps: [
                new JobStep(
                    JobSteps::ZIP_CREATION->value,
                    ZipDownloadMessage::class,
                    '',
                    [self::ASSETS_TO_ZIP => $items]
                ),
            ],
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
    public function createLocalArchive(
        string $localPath,
        bool $create = false
    ): ZipArchive {
        $archive = new ZipArchive();
        $flags = $create ? ZipArchive::CREATE : 0;
        $state = $archive->open($localPath, $flags);

        if ($state !== true) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to %s zip archive at %s.',
                    $create ? 'create' : 'open',
                    $localPath
                )
            );
        }

        return $archive;
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
            $localArchive = fopen($localPath . '/' . $archiveFileName, 'wb');
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

        return $this->createLocalArchive($localPath . '/' . $archiveFileName);
    }

    /**
     * @throws EnvironmentException
     */
    private function validateDownloadItems(array $items): void
    {
        if (count($items) > $this->downloadLimits[DownloadLimits::MAX_ZIP_FILE_AMOUNT->value]) {
            throw new EnvironmentException(
                sprintf(
                    'Too many assets selected. Maximum amount of assets, which can be processed at once is %s',
                    $this->downloadLimits[DownloadLimits::MAX_ZIP_FILE_AMOUNT->value]
                )
            );
        }

        try {
            $totalFileSize = $this->assetSearchService->getTotalFileSizeByIds($items);
        } catch (AssetSearchException) {
            throw new EnvironmentException('One or more selected assets could not be found.');
        }

        if ($totalFileSize > $this->downloadLimits[DownloadLimits::MAX_ZIP_FILE_SIZE->value]) {
            throw new EnvironmentException(
                sprintf(
                    'The total size of the selected assets exceeds the maximum size of %s bytes.',
                    $this->downloadLimits[DownloadLimits::MAX_ZIP_FILE_SIZE->value]
                )
            );
        }
    }
}
