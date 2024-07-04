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

namespace Pimcore\Bundle\StudioBackendBundle\Role\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\Role\Hydrator\RoleTreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\FolderRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter;

/**
 * @internal
 */
final readonly class FolderService implements FolderServiceInterface
{
    public function __construct(
        private FolderRepositoryInterface $folderRepository,
        private RoleTreeNodeHydratorInterface $roleTreeNodeHydrator
    ) {
    }

    /**
     * @throws NotFoundException|DatabaseException
     */
    public function deleteFolder(int $folderId): void
    {
        $folder = $this->folderRepository->getFolderById($folderId);

        try {
            $this->folderRepository->deleteFolder($folder);
        } catch (Exception $exception) {
            throw new DatabaseException(
                sprintf(
                    'Failed to delete folder with id %d: %s',
                    $folderId,
                    $exception->getMessage()
                ));
        }
    }

    public function createFolder(CreateParameter $createParameter): TreeNode
    {
        $parentFolderId = 0;
        if($createParameter->getParentId() !== 0) {
            $parentFolderId = $this->folderRepository->getFolderById($createParameter->getParentId())->getId();
        }

        try {
            $folder = $this->folderRepository->createFolder(
                $createParameter->getName(),
                $parentFolderId
            );

            return $this->roleTreeNodeHydrator->hydrate($folder);

        } catch (Exception $exception) {
            throw new DatabaseException(
                sprintf(
                    'Failed to create folder with name %s: %s',
                    $createParameter->getName(),
                    $exception->getMessage()
                )
            );
        }
    }
}
