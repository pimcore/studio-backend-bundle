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

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\CreateAssetFileParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\Asset;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

/**
 * @internal
 */
interface ZipServiceInterface
{
    public const ASSETS_TO_ZIP = 'assets_to_zip';

    public const DOWNLOAD_ZIP_FILE_NAME = 'download-zip-{id}.zip';

    public const DOWNLOAD_ZIP_FILE_NAME_LOCAL = 'local-' . self::DOWNLOAD_ZIP_FILE_NAME;

    public const DOWNLOAD_ZIP_FOLDER_NAME = 'download-zip-{id}';

    public const DOWNLOAD_ZIP_FILE_PATH = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . self::DOWNLOAD_ZIP_FILE_NAME;

    public const UPLOAD_ZIP_FILE_NAME = 'upload-zip-{id}.zip';

    public const UPLOAD_ZIP_FILE_NAME_LOCAL = 'local-' . self::UPLOAD_ZIP_FILE_NAME;

    public const UPLOAD_ZIP_FOLDER_NAME = 'upload-zip-{id}';

    public const UPLOAD_ZIP_FOLDER_PATH = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . self::UPLOAD_ZIP_FOLDER_NAME;

    public const UPLOAD_ZIP_FILE_PATH = self::UPLOAD_ZIP_FOLDER_PATH . '/' . self::UPLOAD_ZIP_FILE_NAME_LOCAL;

    public function addFile(ZipArchive $archive, Asset $asset): void;

    public function extractArchiveFiles(
        ZipArchive $archive,
        string $targetPath
    ): array;

    /**
     * @throws AccessDeniedException|EnvironmentException|ForbiddenException|NotFoundException
     */
    public function uploadZipAssets(
        UserInterface $user,
        UploadedFile $zipArchive,
        int $parentId
    ): int;

    public function generateZipFile(CreateAssetFileParameter $parameter): int;

    /**
     * @throws EnvironmentException
     */
    public function createLocalArchive(
        string $localPath,
        bool $create = false
    ): ZipArchive;

    /**
     * @throws EnvironmentException
     */
    public function copyZipFileToFlysystem(
        string $id,
        string $folderName,
        string $archiveName,
        string $localPath
    ): void;

    /**
     * @throws EnvironmentException
     */
    public function downloadZipFileFromFlysystem(
        string $id,
        string $folderName,
        string $archiveName,
        string $localPath
    ): ZipArchive;

    public function getTempFilePath(mixed $id, string $path): string;

    public function getTempFileName(mixed $id, string $fileName): string;

}
