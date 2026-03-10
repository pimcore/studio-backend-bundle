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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport;

use const JSON_THROW_ON_ERROR;
use Exception;
use JsonException;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToReadFile;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException as ApiInvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use function json_decode;

/**
 * @internal
 */
final readonly class BulkImportFileService implements BulkImportFileServiceInterface
{
    private const string FILE_PREFIX = 'bulk-import-';

    public function __construct(
        private StorageServiceInterface $storageService,
    ) {
    }

    public function storeFile(UploadedFile $file): string
    {
        try {
            $fileContent = $file->getContent();
            json_decode($fileContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ApiInvalidArgumentException(
                'Failed to parse import data: ' . $e->getMessage(),
                $e
            );
        } catch (Exception $e) {
            throw new EnvironmentException(
                'Failed to read uploaded file: ' . $e->getMessage(),
                previous: $e
            );
        } finally {
            @unlink($file->getPathname());
        }

        $fileId = uniqid('', true);
        $storagePath = $this->buildStoragePath($fileId);

        try {
            $this->storageService->getTempStorage()->write($storagePath, $fileContent);
        } catch (Exception|FilesystemException $e) {
            throw new EnvironmentException(
                'Failed to store import file: ' . $e->getMessage(),
                previous: $e
            );
        }

        return $fileId;
    }

    public function readFileData(string $fileId): array
    {
        $storagePath = $this->buildStoragePath($fileId);

        try {
            $fileContent = $this->storageService->getTempStorage()->read($storagePath);

            return json_decode($fileContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (UnableToReadFile $e) {
            throw new NotFoundException('Bulk import file', $fileId, 'fileId', $e);
        } catch (Exception $e) {
            throw new EnvironmentException(
                'Failed to read import file: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function deleteBulkFile(string $fileId): void
    {
        $storagePath = $this->buildStoragePath($fileId);

        if (!$this->storageService->tempFileExists($storagePath)) {
            throw new NotFoundException('Bulk import file', $fileId, 'fileId');
        }

        $this->storageService->removeTempFile($storagePath);
    }

    public function cleanUpFile(string $fileId): void
    {
        $this->storageService->cleanUpFlysystemFile(
            $this->buildStoragePath($fileId)
        );
    }

    private function buildStoragePath(string $fileId): string
    {
        return self::FILE_PREFIX . $fileId . '.json';
    }
}
