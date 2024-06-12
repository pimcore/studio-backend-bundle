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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserFolderRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserFolderService;
use Pimcore\Model\User\Folder;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class UserFolderServiceTest extends Unit
{
    public function testDeleteUserFolderByIdAsNonAdminUser(): void
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => false]),
        ]);
        $userFolderRepository = $this->makeEmpty(UserFolderRepositoryInterface::class);

        $userFolderService = new UserFolderService($securityService, $userFolderRepository);

        $this->expectExceptionMessage('Only admin users are allowed to delete user folders');
        $this->expectException(ForbiddenException::class);
        $userFolderService->deleteUserFolderById(1);
    }

    public function testDeleteUserFolderByIdWithDatabaseException(): void
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
        ]);

        $userFolderRepository = $this->makeEmpty(UserFolderRepositoryInterface::class, [
            'getUserFolderById' => new Folder(),
            'deleteUserFolder' => function (Folder $folder) {
                throw new Exception('Database error');
            },
        ]);

        $userFolderService = new UserFolderService($securityService, $userFolderRepository);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Failed to delete user folder with id 1: Database error');
        $userFolderService->deleteUserFolderById(1);
    }

    public function testDeleteUserFolderById(): void
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
        ]);

        $userFolderRepository = $this->makeEmpty(UserFolderRepositoryInterface::class, [
            'getUserFolderById' => new Folder(),
            'deleteUserFolder' => Expected::once(),
        ]);

        $userFolderService = new UserFolderService($securityService, $userFolderRepository);
        $userFolderService->deleteUserFolderById(1);
    }
}
