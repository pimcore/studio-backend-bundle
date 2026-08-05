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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\StorageResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\StorageDirectories;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\TempFilePathTrait;
use Symfony\Component\Filesystem\Filesystem;
use function sprintf;

/**
 * @internal
 */
final readonly class StorageService implements StorageServiceInterface
{
    use TempFilePathTrait;

    public function __construct(
        private Filesystem $filesystem,
        private StorageResolverInterface $storageResolver,
    ) {
    }

    /**
     * @throws EnvironmentException
     */
    public function removeTempFile(string $location): void
    {
        $storage = $this->getTempStorage();

        try {
            $storage->delete($location);
        } catch (FilesystemException $e) {
            throw new EnvironmentException(
                sprintf(
                    'Could not remove file %s: %s',
                    $location,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * @throws EnvironmentException
     */
    public function tempFileExists(string $location): bool
    {
        $storage = $this->getTempStorage();

        try {
            return $storage->fileExists($location);
        } catch (FilesystemException $e) {
            throw new EnvironmentException(
                sprintf(
                    'Could not look for file %s: %s',
                    $location,
                    $e->getMessage()
                )
            );
        }
    }

    public function copyElementToFlysystem(
        string $innerPath,
        string $localElementPath,
        string $targetPath,
    ): void {
        match (true) {
            is_file($localElementPath) => $this->copyFileToFlysystem($innerPath, $localElementPath, $targetPath),
            is_dir($localElementPath) => $this->copyFolderToFlysystem($innerPath, $targetPath),
            default => throw new EnvironmentException(
                sprintf(
                    'The element with path %s could not be copied to Flysystem.',
                    $localElementPath
                )
            )
        };
    }

    /**
     * @throws FilesystemException
     */
    public function cleanUpFolder(
        string $folder,
        bool $removeContents = false
    ): void {
        $storage = $this->getTempStorage();

        if ($removeContents || empty($storage->listContents($folder)->toArray())) {
            $storage->deleteDirectory($folder);
        }
    }

    public function cleanUpLocalFolder(
        string $folderLocation
    ): void {
        if ($this->filesystem->exists($folderLocation)) {
            $this->filesystem->remove($folderLocation);
        }
    }

    public function cleanUpLocalFile(
        string $filePath
    ): void {
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    public function cleanUpFlysystemFile(
        string $filePath
    ): void {
        if ($this->tempFileExists($filePath)) {
            $this->removeTempFile($filePath);
        }
    }

    public function getThumbnailStorage(): FilesystemOperator
    {
        return $this->storageResolver->get(StorageDirectories::THUMBNAIL->value);
    }

    public function getTempStorage(): FilesystemOperator
    {
        return $this->storageResolver->get(StorageDirectories::TEMP->value);
    }

    /**
     * @throws EnvironmentException
     */
    private function copyFileToFlysystem(
        string $fileName,
        string $localFilePath,
        string $targetPath,
    ): void {
        try {
            $stream = fopen($localFilePath, 'rb');
            $this->getTempStorage()->writeStream(
                $targetPath . '/' . $fileName,
                $stream
            );
            if (is_resource($stream)) {
                fclose($stream);
            }
            @unlink($localFilePath);
        } catch (FilesystemException) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to copy file %s to Flysystem.',
                    $fileName
                )
            );
        }
    }

    /**
     * @throws EnvironmentException
     */
    private function copyFolderToFlysystem(
        string $folderName,
        string $targetPath
    ): void {
        $storage = $this->getTempStorage();
        $storagePath = $targetPath . '/' . $folderName;

        try {
            if ($storage->directoryExists($storagePath)) {
                return;
            }

            $storage->createDirectory($storagePath);
        } catch (FilesystemException) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to copy folder %s to Flysystem.',
                    $folderName
                )
            );
        }
    }
}
