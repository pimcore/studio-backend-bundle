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
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\SimpleUserHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserTreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\RateLimiter\RateLimiterInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserFolderRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\MailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserService;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[CoversClass(UserService::class)]
#[UsesClass(ForbiddenException::class)]
#[UsesClass(DatabaseException::class)]
#[UsesClass(AbstractApiException::class)]
final class UserServiceTest extends TestCase
{
    public function testDeleteUserWhenUserToDeleteIsAdminButCurrentUserNot(): void
    {
        $userToDelete = new User();
        $userToDelete->setAdmin(true);

        $currentUserMock = $this->createMock(UserInterface::class);
        $currentUserMock->method('isAdmin')->willReturn(false);

        $securityServiceMock = $this->createMock(SecurityServiceInterface::class);
        $securityServiceMock->method('getCurrentUser')->willReturn($currentUserMock);

        $userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $userRepositoryMock->method('getUserById')->willReturn($userToDelete);

        $userService = $this->getUserService($securityServiceMock, $userRepositoryMock);

        $this->expectExceptionMessage('Only admins can delete other admins');
        $this->expectException(ForbiddenException::class);
        $userService->deleteUser(1);
    }

    public function testDeleteUserWithDatabaseException(): void
    {
        $userToDelete = new User();
        $userToDelete->setAdmin(false);

        $currentUserMock = $this->createMock(UserInterface::class);
        $currentUserMock->method('isAdmin')->willReturn(true);

        $securityServiceMock = $this->createMock(SecurityServiceInterface::class);
        $securityServiceMock->method('getCurrentUser')->willReturn($currentUserMock);

        $userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $userRepositoryMock->method('getUserById')->willReturn($userToDelete);
        $userRepositoryMock->method('deleteUser')->willThrowException(new Exception('Database error'));

        $userService = $this->getUserService($securityServiceMock, $userRepositoryMock);

        $this->expectExceptionMessage('Error deleting user with id 1: Database error');
        $this->expectException(DatabaseException::class);
        $userService->deleteUser(1);
    }

    public function testDeleteUser(): void
    {
        $userToDelete = new User();
        $userToDelete->setAdmin(false);

        $currentUserMock = $this->createMock(UserInterface::class);
        $currentUserMock->method('isAdmin')->willReturn(true);

        $securityServiceMock = $this->createMock(SecurityServiceInterface::class);
        $securityServiceMock->method('getCurrentUser')->willReturn($currentUserMock);

        $userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $userRepositoryMock->method('getUserById')->willReturn($userToDelete);
        $userRepositoryMock->expects($this->once())->method('deleteUser')->with($userToDelete);

        $userService = $this->getUserService($securityServiceMock, $userRepositoryMock);

        $userService->deleteUser(1);

        // The test passes if no exception is thrown and deleteUser is called once
        $this->assertTrue(true);
    }

    private function getUserService(
        SecurityServiceInterface $securityServiceMock,
        UserRepositoryInterface $userRepositoryMock
    ): UserService {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $authenticationResolverMock = $this->createMock(AuthenticationResolverInterface::class);
        $userResolverMock = $this->createMock(UserResolverInterface::class);
        $mailServiceMock = $this->createMock(MailServiceInterface::class);
        $rateLimiterMock = $this->createMock(RateLimiterInterface::class);
        $userTreeNodeHydratorMock = $this->createMock(UserTreeNodeHydratorInterface::class);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $userFolderRepositoryMock = $this->createMock(UserFolderRepositoryInterface::class);
        $userHydratorMock = $this->createMock(UserHydratorInterface::class);
        $simpleUserHydratorMock = $this->createMock(SimpleUserHydratorInterface::class);

        return new UserService(
            $authenticationResolverMock,
            $userResolverMock,
            $mailServiceMock,
            $rateLimiterMock,
            $loggerMock,
            $userRepositoryMock,
            $userTreeNodeHydratorMock,
            $eventDispatcherMock,
            $securityServiceMock,
            $userFolderRepositoryMock,
            $userHydratorMock,
            $simpleUserHydratorMock
        );
    }
}
