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

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\SimpleUserHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserTreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserFolderRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\User as UserSchema;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserService;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use ReflectionClass;
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

    public function testDeleteUserWhenBothAreAdmins(): void
    {
        $userToDelete = new User();
        $userToDelete->setAdmin(true);

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

    public function testGetUserByIdThrowsForbiddenWhenNonAdminViewsAdmin(): void
    {
        $adminUser = new User();
        $adminUser->setAdmin(true);

        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => false]),
        ]);

        $userRepositoryMock = $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => $adminUser,
        ]);

        $userService = $this->getUserService($securityServiceMock, $userRepositoryMock);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Only admins can view other admins');
        $userService->getUserById(1);
    }

    public function testGetUserByIdSuccess(): void
    {
        $user = new User();
        $user->setAdmin(false);

        $userSchema = (new ReflectionClass(UserSchema::class))->newInstanceWithoutConstructor();

        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
        ]);

        $userRepositoryMock = $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => $user,
        ]);

        $userHydratorMock = $this->makeEmpty(UserHydratorInterface::class, [
            'hydrate' => $userSchema,
        ]);

        $eventDispatcherMock = $this->makeEmpty(EventDispatcherInterface::class, [
            'dispatch' => Expected::once(function (object $event) {
                return $event;
            }),
        ]);

        $userService = $this->getUserService(
            $securityServiceMock,
            $userRepositoryMock,
            $eventDispatcherMock,
            $userHydratorMock
        );

        $result = $userService->getUserById(1);
        $this->assertSame($userSchema, $result);
    }

    public function testGetUserNameByIdReturnsName(): void
    {
        $user = new User();
        $user->setName('johndoe');

        $userRepositoryMock = $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => $user,
        ]);

        $userService = $this->getUserService(
            $this->makeEmpty(SecurityServiceInterface::class),
            $userRepositoryMock
        );

        $this->assertSame('johndoe', $userService->getUserNameById(1));
    }

    public function testGetUserNameByIdReturnsNullWhenNotFound(): void
    {
        $userRepositoryMock = $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => function () {
                throw new NotFoundException('User', 999);
            },
        ]);

        $userService = $this->getUserService(
            $this->makeEmpty(SecurityServiceInterface::class),
            $userRepositoryMock
        );

        $this->assertNull($userService->getUserNameById(999));
    }

    public function testGetUserNamesByIdsReturnsMapKeyedByIdAndOmitsMissing(): void
    {
        $alice = new User();
        $alice->setId(10);
        $alice->setName('alice');

        $bob = new User();
        $bob->setId(11);
        $bob->setName('bob');

        // user 99 was requested but no longer exists, so the repository does not return it
        $userRepositoryMock = $this->makeEmpty(UserRepositoryInterface::class, [
            'getUsersByIds' => [$alice, $bob],
        ]);

        $userService = $this->getUserService(
            $this->makeEmpty(SecurityServiceInterface::class),
            $userRepositoryMock
        );

        $this->assertSame(
            [10 => 'alice', 11 => 'bob'],
            $userService->getUserNamesByIds([10, 11, 99])
        );
    }

    private function getUserService(
        SecurityServiceInterface $securityServiceMock,
        UserRepositoryInterface $userRepositoryMock,
        ?EventDispatcherInterface $eventDispatcherMock = null,
        ?UserHydratorInterface $userHydratorMock = null,
    ): UserService {
        $userTreeNodeHydratorMock = $this->makeEmpty(UserTreeNodeHydratorInterface::class);
        $userFolderRepositoryMock = $this->makeEmpty(UserFolderRepositoryInterface::class);
        $simpleUserHydratorMock = $this->makeEmpty(SimpleUserHydratorInterface::class);

        return new UserService(
            $userRepositoryMock,
            $userTreeNodeHydratorMock,
            $eventDispatcherMock ?? $this->makeEmpty(EventDispatcherInterface::class),
            $securityServiceMock,
            $userFolderRepositoryMock,
            $userHydratorMock ?? $this->makeEmpty(UserHydratorInterface::class),
            $simpleUserHydratorMock
        );
    }
}
