<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */


namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserFolderRepositoryInterface;
use Pimcore\Model\User\Folder;

/**
 * @internal
 */
final readonly class UserFolderService implements UserFolderServiceInterface
{
    public function __construct(
        private SecurityServiceInterface $securityService,
        private UserFolderRepositoryInterface $userFolderRepository
    )
    {
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function deleteUserFolderById(int $folderId): void
    {
        if (!$this->securityService->getCurrentUser()->isAdmin()) {
            throw new ForbiddenException('Only admin users are allowed to delete user folders');
        }

        $folder = Folder::getById($folderId);

        if (!$folder instanceof Folder) {
            throw new NotFoundException(sprintf('User folder with id %s not found', $folderId));
        }

        try {
            $this->userFolderRepository->deleteUserFolder($folder);
        } catch (Exception $e) {
            throw new DatabaseException(
                sprintf('Failed to delete user folder with id %d: %s', $folderId, $e->getMessage())
            );
        }
    }
}