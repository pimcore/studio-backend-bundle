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

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\PermissionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UpdateService;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Model\User;
use Pimcore\Model\User\Permission\Definition;
use Pimcore\Model\User\Role;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class UpdateServiceTest extends Unit
{
    public function testKnownPermissionsAreStored(): void
    {
        $user = $this->createUser(['assets']);
        $service = $this->createService('assets', 'documents');

        $service->updatePermissions(['assets', 'documents'], $user);

        $this->assertSame(['assets', 'documents'], $user->getPermissions());
    }

    public function testUnknownPermissionIsReportedAsNotFound(): void
    {
        $user = $this->createUser(['assets']);
        $service = $this->createService('assets', 'documents');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Permission with Key: made_up not found');
        $service->updatePermissions(['assets', 'made_up'], $user);
    }

    public function testNonAdminCannotGrantPermissionTheyDoNotHoldThemselves(): void
    {
        $user = $this->createUser([]);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
            'isAllowed' => false,
        ]);
        $service = $this->createServiceWithCurrentUser($currentUser, 'assets', 'documents');

        $this->expectException(ForbiddenException::class);
        $service->updatePermissions(['assets'], $user);
    }

    public function testNonAdminCanGrantPermissionsTheyAlreadyHoldThemselves(): void
    {
        $user = $this->createUser([]);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
            'isAllowed' => static fn (string $permission): bool => $permission === 'assets',
        ]);
        $service = $this->createServiceWithCurrentUser($currentUser, 'assets', 'documents');

        $service->updatePermissions(['assets'], $user);

        $this->assertSame(['assets'], $user->getPermissions());
    }

    public function testAdminCanGrantAnyPermission(): void
    {
        $user = $this->createUser([]);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => true,
        ]);
        $service = $this->createServiceWithCurrentUser($currentUser, 'assets', 'documents');

        $service->updatePermissions(['assets', 'documents'], $user);

        $this->assertSame(['assets', 'documents'], $user->getPermissions());
    }

    public function testNonAdminCannotAssignRoleTheyDoNotHoldThemselves(): void
    {
        $user = $this->createUser([]);
        $currentUser = $this->createNonAdminWithRoles([2]);
        $service = $this->createRoleService([$this->createRole(1), $this->createRole(2)], $currentUser);

        $this->expectException(ForbiddenException::class);
        $service->updateRoles([1], $user);
    }

    public function testNonAdminCanAssignRoleTheyHoldThemselves(): void
    {
        $user = $this->createUser([]);
        $currentUser = $this->createNonAdminWithRoles([1]);
        $service = $this->createRoleService([$this->createRole(1)], $currentUser);

        $service->updateRoles([1], $user);

        $this->assertSame([1], $user->getRoles());
    }

    public function testNonAdminCanClearRoles(): void
    {
        $user = $this->createUser([]);
        $user->setRoles([1]);
        $currentUser = $this->createNonAdminWithRoles([]);
        $service = $this->createRoleService([$this->createRole(1)], $currentUser);

        $service->updateRoles([], $user);

        $this->assertSame([], $user->getRoles());
    }

    public function testAdminCanAssignAnyRole(): void
    {
        $user = $this->createUser([]);
        $service = $this->createRoleService([$this->createRole(1)], $this->createAdminUser());

        $service->updateRoles([1], $user);

        $this->assertSame([1], $user->getRoles());
    }

    public function testUnknownRoleIsReportedAsNotFound(): void
    {
        $user = $this->createUser([]);
        $service = $this->createRoleService([$this->createRole(1)], $this->createAdminUser());

        $this->expectException(NotFoundException::class);
        $service->updateRoles([99], $user);
    }

    /**
     * @dataProvider nonIntegerRoleIdProvider
     */
    public function testNonIntegerRoleIdIsReportedAsNotFound(mixed $roleId): void
    {
        $user = $this->createUser([]);
        $service = $this->createRoleService([$this->createRole(1)], $this->createAdminUser());

        $this->expectException(NotFoundException::class);
        $service->updateRoles([$roleId], $user);
    }

    public function testNonIntegerRoleIdIsReportedAsNotFoundForNonAdminCallers(): void
    {
        $user = $this->createUser([]);
        $service = $this->createRoleService([$this->createRole(1)], $this->createNonAdminWithRoles([1]));

        $this->expectException(NotFoundException::class);
        $service->updateRoles(['1'], $user);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function nonIntegerRoleIdProvider(): array
    {
        return [
            'numeric string' => ['1'],
            'non-numeric string' => ['abc'],
            'float' => [1.5],
            'null' => [null],
            'array' => [[1]],
        ];
    }

    public function testNonAdminCannotLeaveTargetWithPermissionTheyDoNotHold(): void
    {
        $user = $this->createUser(['assets', 'documents']);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
            'isAllowed' => static fn (string $permission): bool => $permission === 'assets',
        ]);
        $service = $this->createServiceWithCurrentUser($currentUser, 'assets', 'documents');

        $this->expectException(ForbiddenException::class);
        $service->updatePermissions(['assets', 'documents'], $user);
    }

    public function testNonAdminCanReduceTargetToPermissionsTheyHold(): void
    {
        $user = $this->createUser(['assets', 'documents']);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
            'isAllowed' => static fn (string $permission): bool => $permission === 'assets',
        ]);
        $service = $this->createServiceWithCurrentUser($currentUser, 'assets', 'documents');

        $service->updatePermissions(['assets'], $user);

        $this->assertSame(['assets'], $user->getPermissions());
    }

    private function createUser(array $permissions): User
    {
        $user = new User();
        $user->setName('testuser');
        $user->setPermissions($permissions);

        return $user;
    }

    private function createService(string ...$availablePermissionKeys): UpdateService
    {
        return $this->createServiceWithCurrentUser($this->createAdminUser(), ...$availablePermissionKeys);
    }

    private function createServiceWithCurrentUser(
        UserInterface $currentUser,
        string ...$availablePermissionKeys
    ): UpdateService {
        $definitions = array_map(
            static fn (string $key) => (new Definition())->setKey($key),
            $availablePermissionKeys
        );

        return new UpdateService(
            $this->makeEmpty(PermissionRepositoryInterface::class, [
                'getAvailablePermissions' => $definitions,
            ]),
            $this->makeEmpty(RoleRepositoryInterface::class),
            $this->makeEmpty(ClassDefinitionRepositoryInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(UserPerspectiveServiceInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $currentUser,
            ]),
        );
    }

    /**
     * @param Role[] $availableRoles
     */
    private function createRoleService(array $availableRoles, UserInterface $currentUser): UpdateService
    {
        return new UpdateService(
            $this->makeEmpty(PermissionRepositoryInterface::class),
            $this->makeEmpty(RoleRepositoryInterface::class, [
                'getRoles' => $availableRoles,
            ]),
            $this->makeEmpty(ClassDefinitionRepositoryInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(UserPerspectiveServiceInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $currentUser,
            ]),
        );
    }

    private function createRole(int $id): Role
    {
        $role = new Role();
        $role->setId($id);
        $role->setPermissions(['assets']);

        return $role;
    }

    /**
     * @param int[] $roleIds
     */
    private function createNonAdminWithRoles(array $roleIds): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
            'getRoles' => $roleIds,
        ]);
    }

    private function createAdminUser(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, [
            'isAdmin' => true,
        ]);
    }
}
