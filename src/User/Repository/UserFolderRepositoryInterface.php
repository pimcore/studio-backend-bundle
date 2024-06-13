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

namespace Pimcore\Bundle\StudioBackendBundle\User\Repository;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\NotFoundException;
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
}
