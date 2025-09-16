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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Repository;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepository;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class UserRepositoryTest extends TestCase
{
    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepository::getUserById
     */
    public function testGetUserByIdNoUserFound(): void
    {
        $securityServiceMock = $this->createMock(SecurityServiceInterface::class);
        $userResolverMock = $this->createMock(UserResolverInterface::class);
        $userResolverMock->method('getById')->willReturn(null);

        $userRepository = new UserRepository($securityServiceMock, $userResolverMock);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User with ID: 1 not found');
        $userRepository->getUserById(1);
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepository::getUserById
     */
    public function testGetUserById(): void
    {
        $userId = 1;
        $user = new User();
        $user->setId($userId);

        $securityServiceMock = $this->createMock(SecurityServiceInterface::class);
        $userResolverMock = $this->createMock(UserResolverInterface::class);
        $userResolverMock->method('getById')->willReturn($user);

        $userRepository = new UserRepository($securityServiceMock, $userResolverMock);

        $this->assertSame($user, $userRepository->getUserById($userId));
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepository::deleteUser
     */
    public function testDeleteUser(): void
    {
        $securityServiceMock = $this->createMock(SecurityServiceInterface::class);
        $userResolverMock = $this->createMock(UserResolverInterface::class);

        $userMock = $this->createMock(UserInterface::class);
        $userMock->expects($this->once())->method('delete');

        $userRepository = new UserRepository($securityServiceMock, $userResolverMock);
        $userRepository->deleteUser($userMock);
    }
}
