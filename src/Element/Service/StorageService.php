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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\StorageResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\StorageDirectories;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\TempFilePathTrait;
use Symfony\Component\Filesystem\Filesystem;

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
        string $folder
    ): void {
        $storage = $this->getTempStorage();
        if (empty($storage->listContents($folder)->toArray())) {
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
            $this->getTempStorage()->writeStream(
                $targetPath . '/' . $fileName,
                fopen($localFilePath, 'rb')
            );
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
