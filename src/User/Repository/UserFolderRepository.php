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

namespace Pimcore\Bundle\StudioBackendBundle\User\Repository;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\FolderResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\User\Folder;

/**
 * @internal
 */
final readonly class UserFolderRepository implements UserFolderRepositoryInterface
{
    public function __construct(
        private FolderResolverInterface $folderResolver
    ) {
    }

    /**
     * @throws Exception
     */
    public function deleteUserFolder(Folder $folder): void
    {
        $folder->delete();
    }

    /**
     * @throws NotFoundException
     */
    public function getUserFolderById(int $folderId): Folder
    {
        $folder = $this->folderResolver->getById($folderId);

        if (!$folder instanceof Folder) {
            throw new NotFoundException('User folder', $folderId);
        }

        return $folder;
    }

    /**
     * @throws Exception
     */
    public function createUserFolder(string $folderName, int $parentId): Folder
    {
        return $this->folderResolver->create([
            'parentId' => $parentId,
            'name' => $folderName,
        ]);
    }
}
