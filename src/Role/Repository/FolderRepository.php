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

namespace Pimcore\Bundle\StudioBackendBundle\Role\Repository;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\Role\FolderResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\User\Role\Folder;

/**
 * @internal
 */
final readonly class FolderRepository implements FolderRepositoryInterface
{
    public function __construct(
        private FolderResolverInterface $folderResolver
    ) {
    }

    /**
     * @throws Exception
     */
    public function deleteFolder(Folder $folder): void
    {
        $folder->delete();
    }

    /**
     * @throws NotFoundException
     */
    public function getFolderById(int $folderId): Folder
    {
        $folder = $this->folderResolver->getById($folderId);

        if (!$folder instanceof Folder) {
            throw new NotFoundException('Folder', $folderId);
        }

        return $folder;
    }

    /**
     * @throws NotFoundException
     */
    public function createFolder(string $folderName, int $parentId): Folder
    {
        return $this->folderResolver->create([
            'parentId' => $parentId,
            'name' => $folderName,
        ]);
    }
}
