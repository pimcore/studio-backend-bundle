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
}
