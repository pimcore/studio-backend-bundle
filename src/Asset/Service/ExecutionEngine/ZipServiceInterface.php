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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\CreateAssetFileParameter;
use Pimcore\Model\Asset;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

/**
 * @internal
 */
interface ZipServiceInterface
{
    public const ASSETS_INDEX = 'assets';

    public const ZIP_FILE_NAME = 'download-zip-{id}.zip';

    public const DOWNLOAD_ZIP_FILE_PATH = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/download-zip-{id}.zip';

    public const UPLOAD_ZIP_FILE_PATH = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/upload-zip-{id}.zip';

    public function getZipArchive(int $id): ?ZipArchive;

    public function addFile(ZipArchive $archive, Asset $asset): void;

    /**
     * @throws AccessDeniedException|EnvironmentException|ForbiddenException|NotFoundException
     */
    public function uploadZipAssets(
        UserInterface $user,
        UploadedFile $zipArchive,
        int $parentId
    ): int;

    public function generateZipFile(CreateAssetFileParameter $ids): string;

    public function getTempFilePath(int $id, string $path): string;
}
