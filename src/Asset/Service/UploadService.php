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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Exception;
use League\Flysystem\FilesystemException;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\AssetUploadMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use function dirname;
use function sprintf;

/**
 * @internal
 */
final readonly class UploadService implements UploadServiceInterface
{
    public function __construct(
        private AssetServiceInterface $assetService,
        private AssetResolverInterface $assetResolver,
        private AssetServiceResolverInterface $assetServiceResolver,
        private JobExecutionAgentInterface $jobExecutionAgent,
        private ServiceResolverInterface $serviceResolver,
        private StorageServiceInterface $storageService,
        private SynchronousProcessingServiceInterface $synchronousProcessingService,
    ) {

    }

    /**
     * @throws AccessDeniedException
     * @throws NotFoundException
     */
    public function fileExists(
        int $parentId,
        string $fileName,
        UserInterface $user
    ): bool {
        $parent = $this->assetService->getAssetElement($user, $parentId);

        return $this->assetServiceResolver->pathExists(
            $parent->getRealFullPath() . '/' . $fileName,
            ElementTypes::TYPE_ASSET
        );
    }

    /**
     * @throws AccessDeniedException
     * @throws DatabaseException
     * @throws EnvironmentException
     * @throws FilesystemException
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function uploadAsset(
        int $parentId,
        string $fileName,
        string $filePath,
        UserInterface $user,
        bool $useFlysystem = false
    ): int {
        $parent = $this->validateParent($user, $parentId);
        $fileName = $this->getValidFileName($fileName);
        $uniqueName = $this->assetService->getUniqueAssetName($parent->getRealFullPath(), $fileName);
        $userId = $user->getId();
        $assetParams = [
            'filename' => $uniqueName,
            'userOwner' => $userId,
            'userModification' => $userId,
        ];
        $this->synchronousProcessingService->enable();

        if ($useFlysystem) {
            return $this->uploadAssetFromFlysystem($parentId, $assetParams, $filePath);
        }

        return $this->uploadAssetLocally($parentId, $assetParams, $filePath);
    }

    public function uploadParentFolder(string $filePath, int $rootParentId, UserInterface $user): int
    {
        $rootParent = $this->validateParent($user, $rootParentId);
        $this->synchronousProcessingService->enable();
        $parent = $this->assetServiceResolver->createFolderByPath(
            $rootParent->getRealFullPath() . '/' . preg_replace('@^/@', '', dirname($filePath))
        );

        return $parent->getId();
    }

    /**
     * @throws EnvironmentException
     */
    public function uploadAssetsAsynchronously(
        UserInterface $user,
        array $files,
        int $parentId,
        string $folderName,
    ): int {
        $job = new Job(
            name: Jobs::UPLOAD_ASSETS->value,
            steps: [
                new JobStep(JobSteps::ASSET_UPLOADING->value, AssetUploadMessage::class, '', []),
            ],
            selectedElements: array_map(static function ($file, $index) {
                try {
                    $fileData = json_encode($file, JSON_THROW_ON_ERROR);

                    return new ElementDescriptor($fileData, $index);
                } catch (Exception $e) {
                    throw new EnvironmentException($e->getMessage());
                }
            }, $files, array_keys($files)),
            environmentData: [
                EnvironmentVariables::PARENT_ID->value => $parentId,
                EnvironmentVariables::UPLOAD_FOLDER_LOCATION->value => $folderName,
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
     * @throws AccessDeniedException
     * @throws DatabaseException
     * @throws EnvironmentException
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function replaceAssetBinary(
        int $assetId,
        UploadedFile $file,
        UserInterface $user
    ): void {
        $asset = $this->assetService->getAssetElement($user, $assetId);
        if (!$asset->isAllowed(ElementPermissions::PUBLISH_PERMISSION)) {
            throw new ForbiddenException(
                sprintf(
                    'Missing permissions on target Asset %s',
                    $asset->getId()
                )
            );
        }

        $sourcePath = $this->getValidSourcePath($file->getRealPath());
        $fileName = $this->getValidFileName($file->getClientOriginalName());
        $this->validateMimeType($file, $fileName, $asset->getType());

        try {
            $asset->setStream(fopen($sourcePath, 'rb'));
            $asset->setCustomSetting('thumbnails', null);
            if (method_exists($asset, 'getEmbeddedMetaData')) {
                $asset->getEmbeddedMetaData(true);
            }
            $asset->setUserModification($user->getId());
            $asset->setFilename($this->getUpdatedFileName($asset->getFilename(), $fileName, $asset->getParent()));
            $asset->save();
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage());
        } finally {
            @unlink($sourcePath);
        }
    }

    /**
     * @throws AccessDeniedException|EnvironmentException|ForbiddenException|NotFoundException
     */
    public function validateParent(UserInterface $user, int $parentId): ElementInterface
    {
        $parent = $this->assetService->getAssetElement($user, $parentId);
        if (!$parent->isAllowed(ElementPermissions::CREATE_PERMISSION)) {
            throw new ForbiddenException(
                sprintf(
                    'Missing permissions on target Asset %s',
                    $parent->getId()
                )
            );
        }

        if (!$parent instanceof Folder) {
            throw new EnvironmentException('Invalid parent type: ' . $parent->getType());
        }

        return $parent;
    }

    public function sanitizeFileToUpload(string $fileName): ?string
    {
        if (str_starts_with($fileName, '__MACOSX/') ||
            str_ends_with($fileName, '/Thumbs.db')) {
            return null;
        }

        return $fileName;
    }

    /**
     * @throws FilesystemException
     */
    public function cleanupTemporaryUploadFiles(string $location): void
    {
        $this->storageService->cleanUpFolder($location, true);
        $this->storageService->cleanUpLocalFolder(PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $location);
    }

    /**
     * @throws DatabaseException
     * @throws EnvironmentException
     */
    private function uploadAssetLocally(
        int $parentId,
        array $assetParams,
        string $sourcePath,
    ): int {
        $assetParams['sourcePath'] = $this->getValidSourcePath($sourcePath);

        try {
            $asset = $this->assetResolver->create(
                $parentId,
                $assetParams
            );
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage());
        } finally {
            @unlink($sourcePath);
        }

        return $asset->getId();
    }

    /**
     * @throws FilesystemException|EnvironmentException
     */
    private function uploadAssetFromFlysystem(
        int $parentId,
        array $assetParams,
        string $sourcePath,
    ): int {
        $storage = $this->storageService->getTempStorage();
        $assetParams['stream'] = $storage->readStream($sourcePath);

        try {
            $asset = $this->assetResolver->create(
                $parentId,
                $assetParams
            );
        } catch (Exception $e) {
            throw new EnvironmentException($e->getMessage());
        } finally {
            $storage->delete($sourcePath);
        }

        return $asset->getId();
    }

    /**
     * @throws EnvironmentException
     */
    private function getValidSourcePath(string $sourcePath): string
    {
        if (!is_file($sourcePath)) {
            throw new EnvironmentException(
                'Something went wrong, please check upload_max_filesize and post_max_size in your php.ini ' .
                ' as well as the write permissions of your temporary directories.'
            );
        }

        if (filesize($sourcePath) < 1) {
            throw new EnvironmentException('File is empty!');
        }

        return $sourcePath;
    }

    /**
     * @throws EnvironmentException
     */
    private function getValidFileName(string $originalFileName): string
    {
        $fileName = $this->serviceResolver->getValidKey(
            $originalFileName,
            ElementTypes::TYPE_ASSET
        );

        if ($fileName === '') {
            throw new EnvironmentException('Invalid filename');
        }

        return $fileName;
    }

    private function getUpdatedFileName(
        string $originalFileName,
        string $newFileName,
        ElementInterface $parent
    ): string {
        $newExtension = pathinfo($newFileName, PATHINFO_EXTENSION);
        $originalExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        if ($newExtension === $originalExtension) {
            return $newFileName;
        }

        $fileName = preg_replace(
            '/\.' . $originalExtension . '$/i',
            '.' . $newExtension,
            $originalFileName
        );

        return $this->serviceResolver->getSafeCopyName($fileName, $parent);
    }

    private function validateMimeType(
        UploadedFile $file,
        string $fileName,
        string $assetType
    ): void {
        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($file->getRealPath());
        $newType = $this->assetResolver->getTypeFromMimeMapping($mimeType, $fileName);

        if ($newType !== $assetType) {
            throw new EnvironmentException(
                sprintf(
                    'Inconsistent asset binary types: original asset (%s) - new asset (%s)',
                    $assetType,
                    $newType
                )
            );
        }
    }
}
