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
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserUpdateService;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    public function testNonAdminCannotUpdatePasswordOfAnotherUser(): void
    {
        $target = $this->createTargetUser(isAdmin: false);
        $service = $this->createPasswordService($target, $this->createNonAdmin(id: 7));

        try {
            $service->updatePasswordById($this->createPasswordParams(), 42);
            $this->fail('Expected a ForbiddenException');
        } catch (ForbiddenException $exception) {
            $this->assertSame('Only admin can change the password of another user', $exception->getMessage());
        }

        $this->assertNull($target->getPassword());
    }

    public function testNonAdminCanUpdateOwnPassword(): void
    {
        $target = $this->createTargetUser(isAdmin: false);
        $service = $this->createPasswordService($target, $this->createNonAdmin(id: 42));

        $service->updatePasswordById($this->createPasswordParams(), 42);

        $this->assertSame('hashed-password', $target->getPassword());
    }

    public function testAdminCanUpdatePasswordOfAnotherUser(): void
    {
        $target = $this->createTargetUser(isAdmin: false);
        $admin = $this->makeEmpty(UserInterface::class, ['isAdmin' => true, 'getId' => 1]);
        $service = $this->createPasswordService($target, $admin);

        $service->updatePasswordById($this->createPasswordParams(), 42);

        $this->assertSame('hashed-password', $target->getPassword());
    }

    public function testNonAdminCannotUpdatePasswordOfAdminUser(): void
    {
        $service = $this->createPasswordService(
            $this->createTargetUser(isAdmin: true),
            $this->createNonAdmin(id: 7)
        );

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Only admin can update admin user');
        $service->updatePasswordById($this->createPasswordParams(), 42);
    }

    public function testNonAdminCannotChangeEmailOfAnotherUser(): void
    {
        $target = $this->createTargetUser(isAdmin: false);
        $service = $this->createService($target, $this->createNonAdmin(id: 7));

        try {
            $service->updateUserById($this->createUpdateParams(email: 'attacker@example.com'), 42);
            $this->fail('Expected a ForbiddenException');
        } catch (ForbiddenException $exception) {
            $this->assertSame('Only admin can change the email of another user', $exception->getMessage());
        }

        $this->assertSame('test@example.com', $target->getEmail());
    }

    public function testNonAdminCanSaveAnotherUserWithUnchangedEmail(): void
    {
        $target = $this->createTargetUser(isAdmin: false);
        $service = $this->createService($target, $this->createNonAdmin(id: 7));

        $service->updateUserById($this->createUpdateParams(), 42);

        $this->assertSame('test@example.com', $target->getEmail());
    }

    public function testNonAdminCanChangeOwnEmail(): void
    {
        $target = $this->createTargetUser(isAdmin: false);
        $service = $this->createService($target, $this->createNonAdmin(id: 42));

        $service->updateUserById($this->createUpdateParams(email: 'new@example.com'), 42);

        $this->assertSame('new@example.com', $target->getEmail());
    }

    public function testAdminCanChangeEmailOfAnotherUser(): void
    {
        $target = $this->createTargetUser(isAdmin: false);
        $admin = $this->makeEmpty(UserInterface::class, ['isAdmin' => true, 'getId' => 1]);
        $service = $this->createService($target, $admin);

        $service->updateUserById($this->createUpdateParams(email: 'new@example.com'), 42);

        $this->assertSame('new@example.com', $target->getEmail());
    }

    private function createNonAdmin(int $id): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
            'getId' => $id,
        ]);
    }

    private function createPasswordParams(): UpdatePasswordParameter
    {
        return new UpdatePasswordParameter('very-long-password-1', 'very-long-password-1', null);
    }

    private function createPasswordService(User $targetUser, UserInterface $currentUser): UserUpdateService
    {
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
        );
    }

    private function createTargetUser(bool $isAdmin): User
    {
        $user = new User();
        $user->setAdmin($isAdmin);
        $user->setActive(true);
        $user->setName('targetuser');
        $user->setId(42);
        $user->setEmail('test@example.com');

        return $user;
    }

    private function createUpdateParams(bool $admin = false, string $email = 'test@example.com'): UpdateUserParameter
    {
        return new UpdateUserParameter(
            email: $email,
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
        );
    }
}
