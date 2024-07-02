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
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 */
final readonly class UploadService implements UploadServiceInterface
{
    public function __construct(
        private AssetServiceInterface $assetService,
        private AssetResolverInterface $assetResolver,
        private ServiceResolverInterface $serviceResolver,
    ) {
        
    }

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
    ): int
    {
        $this->validateParent($user, $parentId);
        $sourcePath = $this->getValidSourcePath($file);
        $fileName = $this->getValidFileName($file);
        $userId = $user->getId();

        try {
            $asset = $this->assetResolver->create(
                $parentId,
                [
                    'filename' => $fileName,
                    'sourcePath' => $sourcePath,
                    'userOwner' => $userId,
                    'userModification' => $userId,
                ]
            );
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage());
        }

        @unlink($sourcePath);

        return $asset->getId();
    }
    
    /**
     * @throws AccessDeniedException|EnvironmentException|ForbiddenException|NotFoundException
     */
    private function validateParent(UserInterface $user, int $parentId): void
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
    }

    /**
     * @throws EnvironmentException
     */
    private function getValidSourcePath(UploadedFile $file): string
    {
        $sourcePath = $file->getRealPath();
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
    private function getValidFileName(UploadedFile $file): string
    {
        $fileName = $this->serviceResolver->getValidKey(
            $file->getClientOriginalName(),
            ElementTypes::TYPE_ASSET
        );

        if ($fileName === '') {
            throw new EnvironmentException('Invalid filename');
        }

        return $fileName;
    }
}
