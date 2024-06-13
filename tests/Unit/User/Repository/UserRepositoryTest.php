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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Repository;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
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
        $userResolverMock = $this->makeEmpty(UserResolverInterface::class, [
            'getById' => null,
        ]);

        $userRepository = new UserRepository($userResolverMock);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User with ID 1 not found');
        $userRepository->getUserById(1);
    }

    public function testGetUserById(): void
    {
        $userId = 1;
        $user = new User();
        $user->setId($userId);
        $userResolverMock = $this->makeEmpty(UserResolverInterface::class, [
            'getById' => $user,
        ]);

        $userRepository = new UserRepository($userResolverMock);

        $this->assertSame($user, $userRepository->getUserById($userId));
    }

    public function testDeleteUser()
    {
        $userResolverMock = $this->makeEmpty(UserResolverInterface::class);

        $userMock = $this->makeEmpty(UserInterface::class, [
            'delete' => Expected::once(),
        ]);

        $userRepository = new UserRepository($userResolverMock);
        $userRepository->deleteUser($userMock);
    }
}
