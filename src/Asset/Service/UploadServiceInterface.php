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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use League\Flysystem\FilesystemException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 */
interface UploadServiceInterface
{
    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function fileExists(
        int $parentId,
        string $fileName,
        UserInterface $user
    ): bool;

    /**
     * @throws DatabaseException
     * @throws EnvironmentException
     * @throws FilesystemException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function uploadAsset(
        int $parentId,
        string $fileName,
        string $filePath,
        UserInterface $user,
        bool $useFlysystem = false
    ): int;

    /**
     * @throws EnvironmentException
     */
    public function uploadAssetsAsynchronously(
        UserInterface $user,
        array $files,
        int $parentId,
        string $folderName,
    ): int;

    public function uploadParentFolder(string $filePath, int $rootParentId, UserInterface $user): int;

    /**
     * @throws DatabaseException
     * @throws EnvironmentException
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function replaceAssetBinary(
        int $assetId,
        UploadedFile $file,
        UserInterface $user
    ): string;

    /**
     * @throws EnvironmentException|ForbiddenException|NotFoundException
     */
    public function validateParent(UserInterface $user, int $parentId): ElementInterface;

    public function sanitizeFileToUpload(string $fileName): ?string;

    /**
     * @throws FilesystemException
     */
    public function cleanupTemporaryUploadFiles(string $location): void;
}
