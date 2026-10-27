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
use Pimcore\Model\DataObject\ClassDefinition;
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

    public function testOrphanedPermissionOfTheUserIsDroppedInsteadOfRejected(): void
    {
        // The definition of "uninstalled_bundle_permission" is gone from users_permission_definitions,
        // but the key is still assigned to the user and therefore sent back on the next save.
        $user = $this->createUser(['assets', 'uninstalled_bundle_permission']);
        $service = $this->createService('assets', 'documents');

        $service->updatePermissions(['assets', 'uninstalled_bundle_permission'], $user);

        $this->assertSame(['assets'], $user->getPermissions());
    }

    public function testOrphanedPermissionOfARoleIsDroppedInsteadOfRejected(): void
    {
        $role = new Role();
        $role->setPermissions(['assets', 'uninstalled_bundle_permission']);
        $service = $this->createService('assets', 'documents');

        $service->updatePermissions(['assets', 'uninstalled_bundle_permission'], $role);

        $this->assertSame(['assets'], $role->getPermissions());
    }

    public function testOrphanedPermissionOfAnotherUserIsStillReportedAsNotFound(): void
    {
        $user = $this->createUser(['assets']);
        $service = $this->createService('assets', 'documents');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Permission with Key: uninstalled_bundle_permission not found');
        $service->updatePermissions(['assets', 'uninstalled_bundle_permission'], $user);
    }

    public function testStoredPermissionsAreSequentiallyIndexedAfterDroppingOrphans(): void
    {
        $user = $this->createUser(['orphan', 'assets']);
        $service = $this->createService('assets');

        $service->updatePermissions(['orphan', 'assets'], $user);

        $this->assertSame([0], array_keys($user->getPermissions()));
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

    public function testNonAdminCannotAssignRoleGrantingPermissionTheyDoNotHoldThemselves(): void
    {
        $user = $this->createUser([]);
        $role = new Role();
        $role->setId(1);
        $role->setPermissions(['assets']);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
            'isAllowed' => false,
        ]);
        $service = $this->createRoleService([$role], $currentUser);

        $this->expectException(ForbiddenException::class);
        $service->updateRoles([1], $user);
    }

    public function testNonAdminCanAssignRoleWithPermissionsTheyAlreadyHoldThemselves(): void
    {
        $user = $this->createUser([]);
        $role = new Role();
        $role->setId(1);
        $role->setPermissions(['assets']);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
            'isAllowed' => static fn (string $permission): bool => $permission === 'assets',
        ]);
        $service = $this->createRoleService([$role], $currentUser);

        $service->updateRoles([1], $user);

        $this->assertSame([1], $user->getRoles());
    }

    public function testKnownClassesAreStored(): void
    {
        $user = $this->createUser([]);
        $user->setClasses(['CAR']);
        $service = $this->createClassService('CAR', 'NEWS');

        $service->updateClasses(['CAR', 'NEWS'], $user);

        $this->assertSame(['CAR', 'NEWS'], $user->getClasses());
    }

    public function testUnknownClassIsReportedAsNotFound(): void
    {
        $user = $this->createUser([]);
        $user->setClasses(['CAR']);
        $service = $this->createClassService('CAR', 'NEWS');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Class with ID: made_up not found');
        $service->updateClasses(['CAR', 'made_up'], $user);
    }

    public function testDeletedClassOfTheUserIsDroppedInsteadOfRejected(): void
    {
        // The class "DELETED" was removed after it got assigned to the user, so its ID is still stored on the
        // user and sent back on the next save.
        $user = $this->createUser([]);
        $user->setClasses(['CAR', 'DELETED']);
        $service = $this->createClassService('CAR', 'NEWS');

        $service->updateClasses(['CAR', 'DELETED'], $user);

        $this->assertSame(['CAR'], $user->getClasses());
    }

    public function testDeletedClassOfARoleIsDroppedInsteadOfRejected(): void
    {
        $role = new Role();
        $role->setClasses(['CAR', 'DELETED']);
        $service = $this->createClassService('CAR', 'NEWS');

        $service->updateClasses(['DELETED', 'CAR'], $role);

        $this->assertSame(['CAR'], $role->getClasses());
    }

    public function testDeletedClassOfAnotherUserIsStillReportedAsNotFound(): void
    {
        $user = $this->createUser([]);
        $user->setClasses(['CAR']);
        $service = $this->createClassService('CAR', 'NEWS');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Class with ID: DELETED not found');
        $service->updateClasses(['CAR', 'DELETED'], $user);
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

    private function createAdminUser(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, [
            'isAdmin' => true,
        ]);
    }

    private function createClassService(string ...$classIds): UpdateService
    {
        $classDefinitions = array_map(
            static fn (string $id) => (new ClassDefinition())->setId($id),
            $classIds
        );

        return new UpdateService(
            $this->makeEmpty(PermissionRepositoryInterface::class),
            $this->makeEmpty(RoleRepositoryInterface::class),
            $this->makeEmpty(ClassDefinitionRepositoryInterface::class, [
                'getClassDefinitions' => $classDefinitions,
            ]),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(UserPerspectiveServiceInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->createAdminUser(),
            ]),
        );
    }
}
