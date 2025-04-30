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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\User\Role\Folder;

/**
 * @internal
 */
interface FolderRepositoryInterface
{
    /**
     * @throws NotFoundException
     */
    public function getFolderById(int $folderId): Folder;

    /**
     * @throws Exception
     */
    public function deleteFolder(Folder $folder): void;

    /**
     * @throws Exception
     */
    public function createFolder(string $folderName, int $parentId): Folder;
}
