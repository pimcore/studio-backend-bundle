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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\PermissionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UpdateService;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Model\User;
use Pimcore\Model\User\Permission\Definition;
use Pimcore\Model\User\Role;

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

    private function createUser(array $permissions): User
    {
        $user = new User();
        $user->setName('testuser');
        $user->setPermissions($permissions);

        return $user;
    }

    private function createService(string ...$availablePermissionKeys): UpdateService
    {
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
        );
    }
}
