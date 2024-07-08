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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
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
     * @throws AccessDeniedException
     * @throws NotFoundException
     */
    public function fileExists(
        int $parentId,
        string $fileName,
        UserInterface $user
    ): bool;

    /**
     * @throws AccessDeniedException
     * @throws DatabaseException
     * @throws EnvironmentException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function uploadAsset(
        int $parentId,
        UploadedFile $file,
        UserInterface $user
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
    ): void;

    /**
     * @throws AccessDeniedException|EnvironmentException|ForbiddenException|NotFoundException
     */
    public function validateParent(UserInterface $user, int $parentId): ElementInterface;

    public function sanitizeFileToUpload(string $fileName): ?string;
}
