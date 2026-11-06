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
use Pimcore\Bundle\StaticResolverBundle\Lib\CacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdatePasswordParameter;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdateUserParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\PermissionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UpdateService;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UpdateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPermissionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserUpdateService;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function in_array;

/**
 * @internal
 */
final class UserUpdateServiceTest extends Unit
{
    public function testNonAdminCannotUpdateAdminUser(): void
    {
        $targetUser = $this->createTargetUser(isAdmin: true);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
        ]);

        $service = $this->createService($targetUser, $currentUser);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Only admin can update admin user');
        $service->updateUserById($this->createUpdateParams(), 42);
    }

    public function testAdminCanUpdateAdminUser(): void
    {
        $targetUser = $this->createTargetUser(isAdmin: true);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => true,
        ]);

        $service = $this->createService($targetUser, $currentUser);

        $service->updateUserById($this->createUpdateParams(), 42);
    }

    public function testOnlyAdminCanSetAdminFlag(): void
    {
        $targetUser = $this->createTargetUser(isAdmin: false);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
        ]);

        $service = $this->createService($targetUser, $currentUser);

        $params = $this->createUpdateParams(admin: true);
        $service->updateUserById($params, 42);

        $this->assertFalse($targetUser->isAdmin());
    }

    public function testAdminCanPromoteUserToAdmin(): void
    {
        $targetUser = $this->createTargetUser(isAdmin: false);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => true,
        ]);

        $service = $this->createService($targetUser, $currentUser);

        $params = $this->createUpdateParams(admin: true);
        $service->updateUserById($params, 42);

        $this->assertTrue($targetUser->isAdmin());
    }

    public function testNonAdminCanUpdateNonAdminUser(): void
    {
        $targetUser = $this->createTargetUser(isAdmin: false);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
        ]);

        $service = $this->createService($targetUser, $currentUser);

        $service->updateUserById($this->createUpdateParams(), 42);
    }

    public function testNonAdminCannotUpdatePasswordOfUserWithPermissionsTheyDoNotHold(): void
    {
        $service = $this->createPasswordService(
            $this->createTargetUser(isAdmin: false),
            $this->createNonAdmin(['users']),
            ['assets']
        );

        $this->expectException(ForbiddenException::class);
        $service->updatePasswordById($this->createPasswordParams(), 42);
    }

    public function testNonAdminCanUpdatePasswordOfUserWithEqualOrFewerPermissions(): void
    {
        $target = $this->createTargetUser(isAdmin: false);
        $service = $this->createPasswordService($target, $this->createNonAdmin(['users', 'assets']), ['assets']);

        $service->updatePasswordById($this->createPasswordParams(), 42);

        $this->assertSame('hashed-password', $target->getPassword());
    }

    public function testAdminCanUpdatePasswordOfUserWithAnyPermissions(): void
    {
        $target = $this->createTargetUser(isAdmin: false);
        $admin = $this->makeEmpty(UserInterface::class, ['isAdmin' => true]);
        $service = $this->createPasswordService($target, $admin, ['assets', 'documents', 'seo']);

        $service->updatePasswordById($this->createPasswordParams(), 42);

        $this->assertSame('hashed-password', $target->getPassword());
    }

    public function testNonAdminCannotUpdatePasswordOfAdminUser(): void
    {
        $service = $this->createPasswordService(
            $this->createTargetUser(isAdmin: true),
            $this->createNonAdmin(['users', 'assets']),
            []
        );

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Only admin can update admin user');
        $service->updatePasswordById($this->createPasswordParams(), 42);
    }

    /**
     * @param string[] $heldPermissions
     */
    private function createNonAdmin(array $heldPermissions): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
            'isAllowed' => static fn (string $permission): bool => in_array($permission, $heldPermissions, true),
        ]);
    }

    private function createPasswordParams(): UpdatePasswordParameter
    {
        return new UpdatePasswordParameter('very-long-password-1', 'very-long-password-1', null);
    }

    /**
     * @param string[] $targetPermissions
     */
    private function createPasswordService(
        User $targetUser,
        UserInterface $currentUser,
        array $targetPermissions
    ): UserUpdateService {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $currentUser,
        ]);

        return new UserUpdateService(
            $this->makeEmpty(AuthenticationResolverInterface::class, [
                'getPasswordHash' => 'hashed-password',
            ]),
            $this->makeEmpty(CacheResolverInterface::class),
            $securityService,
            $this->makeEmpty(UserRepositoryInterface::class, [
                'getUserById' => $targetUser,
            ]),
            new UpdateService(
                $this->makeEmpty(PermissionRepositoryInterface::class),
                $this->makeEmpty(RoleRepositoryInterface::class),
                $this->makeEmpty(ClassDefinitionRepositoryInterface::class),
                $this->makeEmpty(ServiceResolverInterface::class),
                $this->makeEmpty(UserPerspectiveServiceInterface::class),
                $securityService,
            ),
            $this->makeEmpty(UserPerspectiveServiceInterface::class),
            $this->makeEmpty(ValidatorInterface::class),
            $this->makeEmpty(UserPermissionServiceInterface::class, [
                'getAllowedUserPermissions' => $targetPermissions,
            ]),
        );
    }

    private function createTargetUser(bool $isAdmin): User
    {
        $user = new User();
        $user->setAdmin($isAdmin);
        $user->setActive(true);
        $user->setName('targetuser');

        return $user;
    }

    private function createUpdateParams(bool $admin = false): UpdateUserParameter
    {
        return new UpdateUserParameter(
            email: 'test@example.com',
            firstname: 'Test',
            lastname: 'User',
            active: true,
            admin: $admin,
            classes: [],
            docTypes: [],
            closeWarning: false,
            allowDirtyClose: false,
            contentLanguages: [],
            keyBindings: [],
            language: 'en',
            dateTimeLocale: null,
            memorizeTabs: false,
            parentId: 0,
            permissions: [],
            roles: [],
            twoFactorAuthenticationRequired: false,
            websiteTranslationLanguagesEdit: [],
            websiteTranslationLanguagesView: [],
            welcomeScreen: false,
            assetWorkspaces: [],
            dataObjectWorkspaces: [],
            documentWorkspaces: [],
        );
    }

    private function createService(User $targetUser, UserInterface $currentUser): UserUpdateService
    {
        $updateService = $this->makeEmpty(UpdateServiceInterface::class, [
            'updatePermissions' => $targetUser,
            'updateRoles' => $targetUser,
            'updateClasses' => $targetUser,
            'updateAssetWorkspaces' => $targetUser,
            'updateDataObjectWorkspaces' => $targetUser,
            'updateDocumentWorkspaces' => $targetUser,
            'updatePerspectives' => $targetUser,
        ]);

        return new UserUpdateService(
            $this->makeEmpty(AuthenticationResolverInterface::class),
            $this->makeEmpty(CacheResolverInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $currentUser,
            ]),
            $this->makeEmpty(UserRepositoryInterface::class, [
                'getUserById' => $targetUser,
            ]),
            $updateService,
            $this->makeEmpty(UserPerspectiveServiceInterface::class),
            $this->makeEmpty(ValidatorInterface::class),
            $this->makeEmpty(UserPermissionServiceInterface::class),
        );
    }
}
