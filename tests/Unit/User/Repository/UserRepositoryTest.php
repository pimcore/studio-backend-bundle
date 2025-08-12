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

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepository;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class UserRepositoryTest extends Unit
{
    public function testGetUserByIdNoUserFound(): void
    {
        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class);
        $userResolverMock = $this->makeEmpty(UserResolverInterface::class, [
            'getById' => null,
        ]);

        $userRepository = new UserRepository($securityServiceMock, $userResolverMock);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User with ID: 1 not found');
        $userRepository->getUserById(1);
    }

    public function testGetUserById(): void
    {
        $userId = 1;
        $user = new User();
        $user->setId($userId);

        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class);
        $userResolverMock = $this->makeEmpty(UserResolverInterface::class, [
            'getById' => $user,
        ]);

        $userRepository = new UserRepository($securityServiceMock, $userResolverMock);

        $this->assertSame($user, $userRepository->getUserById($userId));
    }

    public function testDeleteUser()
    {
        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class);
        $userResolverMock = $this->makeEmpty(UserResolverInterface::class);

        $userMock = $this->makeEmpty(UserInterface::class, [
            'delete' => Expected::once(),
        ]);

        $userRepository = new UserRepository($securityServiceMock, $userResolverMock);
        $userRepository->deleteUser($userMock);
    }
}
