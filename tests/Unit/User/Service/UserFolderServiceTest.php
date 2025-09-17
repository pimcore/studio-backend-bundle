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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Service;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserTreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserFolderRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserFolderService;
use Pimcore\Model\User\Folder;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
#[CoversClass(UserFolderService::class)]
#[UsesClass(AbstractApiException::class)]
#[UsesClass(DatabaseException::class)]
#[UsesClass(ForbiddenException::class)]
final class UserFolderServiceTest extends TestCase
{
    public function testDeleteUserFolderByIdAsNonAdminUser(): void
    {
        $currentUserMock = $this->createMock(UserInterface::class);
        $currentUserMock->method('isAdmin')->willReturn(false);

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->method('getCurrentUser')->willReturn($currentUserMock);

        $userFolderRepository = $this->createMock(UserFolderRepositoryInterface::class);
        $userTreeNodeHydrator = $this->createMock(UserTreeNodeHydratorInterface::class);

        $userFolderService = new UserFolderService($securityService, $userFolderRepository, $userTreeNodeHydrator);

        $this->expectExceptionMessage('Only admin users are allowed to delete user folders');
        $this->expectException(ForbiddenException::class);
        $userFolderService->deleteUserFolderById(1);
    }

    public function testDeleteUserFolderByIdWithDatabaseException(): void
    {
        $currentUserMock = $this->createMock(UserInterface::class);
        $currentUserMock->method('isAdmin')->willReturn(true);

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->method('getCurrentUser')->willReturn($currentUserMock);

        $folder = new Folder();
        $userFolderRepository = $this->createMock(UserFolderRepositoryInterface::class);
        $userFolderRepository->method('getUserFolderById')->willReturn($folder);
        $userFolderRepository->method('deleteUserFolder')->willThrowException(new Exception('Database error'));

        $userTreeNodeHydrator = $this->createMock(UserTreeNodeHydratorInterface::class);

        $userFolderService = new UserFolderService($securityService, $userFolderRepository, $userTreeNodeHydrator);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Failed to delete user folder with id 1: Database error');
        $userFolderService->deleteUserFolderById(1);
    }

    public function testDeleteUserFolderById(): void
    {
        $currentUserMock = $this->createMock(UserInterface::class);
        $currentUserMock->method('isAdmin')->willReturn(true);

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->method('getCurrentUser')->willReturn($currentUserMock);

        $folder = new Folder();
        $userFolderRepository = $this->createMock(UserFolderRepositoryInterface::class);
        $userFolderRepository->method('getUserFolderById')->willReturn($folder);
        $userFolderRepository->expects($this->once())->method('deleteUserFolder')->with($folder);

        $userTreeNodeHydrator = $this->createMock(UserTreeNodeHydratorInterface::class);

        $userFolderService = new UserFolderService($securityService, $userFolderRepository, $userTreeNodeHydrator);
        $userFolderService->deleteUserFolderById(1);

        // The test passes if no exception is thrown and deleteUserFolder is called once
        $this->assertTrue(true);
    }
}
