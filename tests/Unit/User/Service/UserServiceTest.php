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
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserTreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\RateLimiter\RateLimiterInterface;
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
final class UserServiceTest extends Unit
{
    public function testDeleteUserWhenUserToDeleteIsAdminButCurrentUserNot()
    {
        $userToDelete = new User();
        $userToDelete->setAdmin(true);

        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => false]),
        ]);
        $userRepositoryMock = $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => $userToDelete,
        ]);

        $userService = $this->getUserService($securityServiceMock, $userRepositoryMock);

        $this->expectExceptionMessage('Only admins can delete other admins');
        $this->expectException(ForbiddenException::class);
        $userService->deleteUser(1);
    }

    public function testDeleteUserWithDatabaseException()
    {
        $userToDelete = new User();
        $userToDelete->setAdmin(false);

        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
        ]);

        $userRepositoryMock = $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => $userToDelete,
            'deleteUser' => function (User $user) {
                throw new Exception('Database error');
            },
        ]);

        $userService = $this->getUserService($securityServiceMock, $userRepositoryMock);

        $this->expectExceptionMessage('Error deleting user with id 1: Database error');
        $this->expectException(DatabaseException::class);
        $userService->deleteUser(1);
    }

    public function testDeleteUser()
    {
        $userToDelete = new User();
        $userToDelete->setAdmin(false);

        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
        ]);

        $userRepositoryMock = $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => $userToDelete,
            'deleteUser' => Expected::once(),
        ]);

        $userService = $this->getUserService($securityServiceMock, $userRepositoryMock);

        $userService->deleteUser(1);
    }

    private function getUserService(
        SecurityServiceInterface $securityServiceMock,
        UserRepositoryInterface $userRepositoryMock
    ): UserService {
        $loggerMock = $this->makeEmpty(LoggerInterface::class);
        $authenticationResolverMock = $this->makeEmpty(AuthenticationResolverInterface::class);
        $userResolverMock = $this->makeEmpty(UserResolverInterface::class);
        $mailServiceMock = $this->makeEmpty(MailServiceInterface::class);
        $rateLimiterMock = $this->makeEmpty(RateLimiterInterface::class);
        $userTreeNodeHydratorMock = $this->makeEmpty(UserTreeNodeHydratorInterface::class);
        $eventDispatcherMock = $this->makeEmpty(EventDispatcherInterface::class);

        return new UserService(
            $authenticationResolverMock,
            $userResolverMock,
            $mailServiceMock,
            $rateLimiterMock,
            $loggerMock,
            $userRepositoryMock,
            $userTreeNodeHydratorMock,
            $eventDispatcherMock,
            $securityServiceMock
        );
    }
}
