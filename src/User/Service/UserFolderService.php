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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserFolderRepositoryInterface;

/**
 * @internal
 */
final readonly class UserFolderService implements UserFolderServiceInterface
{
    public function __construct(
        private SecurityServiceInterface $securityService,
        private UserFolderRepositoryInterface $userFolderRepository
    ) {
    }

    /**
     * @throws ForbiddenException|NotFoundException|DatabaseException
     */
    public function deleteUserFolderById(int $folderId): void
    {
        if (!$this->securityService->getCurrentUser()->isAdmin()) {
            throw new ForbiddenException('Only admin users are allowed to delete user folders');
        }

        $folder = $this->userFolderRepository->getUserFolderById($folderId);

        try {
            $this->userFolderRepository->deleteUserFolder($folder);
        } catch (Exception $e) {
            throw new DatabaseException(
                sprintf('Failed to delete user folder with id %d: %s', $folderId, $e->getMessage())
            );
        }
    }
}
