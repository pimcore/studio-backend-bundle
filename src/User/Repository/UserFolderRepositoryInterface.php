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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\User\Folder;

/**
 * @internal
 */
interface UserFolderRepositoryInterface
{
    /**
     * @throws Exception
     */
    public function deleteUserFolder(Folder $folder): void;

    /**
     * @throws NotFoundException
     */
    public function getUserFolderById(int $folderId): Folder;

    /**
     * @throws Exception
     */
    public function createUserFolder(string $folderName, int $parentId): Folder;
}
