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
use Pimcore\Bundle\StaticResolverBundle\Lib\CacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdatePasswordParameter;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdateUserParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\PermissionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UpdateUserProfile;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UpdateService;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UpdateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserSettingsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserUpdateService;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
final class UserUpdateServiceTest extends Unit
{
    private const string WELCOME_SCREEN_PATH = 'Reporting/Dashboards/Dashboards';

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

    public function testPasswordResetWithoutOldPasswordRejectsUnchangedPassword(): void
    {
        $targetUser = $this->createTargetUser(isAdmin: false);
        $authenticationResolver = $this->createAuthenticationResolver(isSamePassword: true, expectedUser: $targetUser);
        $service = $this->createService(
            $targetUser,
            $this->createNonAdmin(id: 42),
            $authenticationResolver
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('New password cannot be the same as the old password');
        $service->updatePasswordById(new UpdatePasswordParameter('new-password-1', 'new-password-1'), 42);
    }

    public function testPasswordResetWithoutOldPasswordAcceptsDifferentPassword(): void
    {
        $targetUser = $this->createTargetUser(isAdmin: false);
        $service = $this->createService(
            $targetUser,
            $this->createNonAdmin(id: 42),
            $this->createAuthenticationResolver(isSamePassword: false)
        );

        $service->updatePasswordById(new UpdatePasswordParameter('new-password-1', 'new-password-1'), 42);

        $this->assertSame('hash-of-new-password', $targetUser->getPassword());
    }

    public function testPasswordChangeWithCorrectOldPasswordAcceptsDifferentPassword(): void
    {
        $targetUser = $this->createTargetUser(isAdmin: false);
        $service = $this->createService(
            $targetUser,
            $this->createNonAdmin(id: 42),
            $this->createAuthenticationResolver(isSamePassword: false),
            $this->createValidator(violations: 0)
        );

        $service->updatePasswordById(
            new UpdatePasswordParameter('new-password-1', 'new-password-1', 'old-password-1'),
            42
        );

        $this->assertSame('hash-of-new-password', $targetUser->getPassword());
    }

    public function testPasswordChangeWithWrongOldPasswordIsRejected(): void
    {
        $targetUser = $this->createTargetUser(isAdmin: false);
        $service = $this->createService(
            $targetUser,
            $this->createNonAdmin(id: 42),
            $this->createAuthenticationResolver(isSamePassword: false),
            $this->createValidator(violations: 1)
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Old password is incorrect');
        $service->updatePasswordById(
            new UpdatePasswordParameter('new-password-1', 'new-password-1', 'wrong-password'),
            42
        );
    }

    public function testUpdateUserStoresTheWelcomeScreenPath(): void
    {
        $targetUser = $this->createTargetUser(isAdmin: false);
        $service = $this->createService(
            targetUser: $targetUser,
            currentUser: $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
            userSettingsService: $this->createUserSettingsService(self::WELCOME_SCREEN_PATH, $targetUser)
        );

        $service->updateUserById($this->createUpdateParams(welcomeScreenPath: self::WELCOME_SCREEN_PATH), 42);
    }

    public function testUpdateUserPassesAnEmptyWelcomeScreenPathOnToClearIt(): void
    {
        $targetUser = $this->createTargetUser(isAdmin: false);
        $service = $this->createService(
            targetUser: $targetUser,
            currentUser: $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
            userSettingsService: $this->createUserSettingsService('', $targetUser)
        );

        $service->updateUserById($this->createUpdateParams(welcomeScreenPath: ''), 42);
    }

    public function testUpdateUserWithoutWelcomeScreenPathKeepsTheStoredPath(): void
    {
        $targetUser = $this->createTargetUser(isAdmin: false);
        $service = $this->createService(
            targetUser: $targetUser,
            currentUser: $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
            userSettingsService: $this->createUserSettingsService(null, $targetUser)
        );

        $service->updateUserById($this->createUpdateParams(), 42);
    }

    public function testUpdateProfileStoresTheWelcomeScreenPath(): void
    {
        $user = $this->createTargetUser(isAdmin: false);
        $service = $this->createService(
            targetUser: $user,
            currentUser: $user,
            userSettingsService: $this->createUserSettingsService(self::WELCOME_SCREEN_PATH, $user)
        );

        $service->updateUserProfile($user, $this->createProfileParams(self::WELCOME_SCREEN_PATH));
    }

    public function testUpdateProfileWithoutWelcomeScreenPathKeepsTheStoredPath(): void
    {
        $user = $this->createTargetUser(isAdmin: false);
        $service = $this->createService(
            targetUser: $user,
            currentUser: $user,
            userSettingsService: $this->createUserSettingsService(null, $user)
        );

        $service->updateUserProfile($user, $this->createProfileParams());
    }

    private function createValidator(int $violations): ValidatorInterface
    {
        $list = new ConstraintViolationList();
        for ($i = 0; $i < $violations; $i++) {
            $list->add(new ConstraintViolation('invalid', null, [], null, '', null));
        }

        return $this->makeEmpty(ValidatorInterface::class, [
            'validate' => Expected::once($list),
        ]);
    }

    private function createAuthenticationResolver(
        bool $isSamePassword,
        ?User $expectedUser = null
    ): AuthenticationResolverInterface {
        $verifyPassword = $expectedUser === null
            ? $isSamePassword
            : Expected::once(function (User $user, string $password) use ($expectedUser, $isSamePassword): bool {
                $this->assertSame($expectedUser, $user);
                $this->assertSame('new-password-1', $password);

                return $isSamePassword;
            });

        return $this->makeEmpty(AuthenticationResolverInterface::class, [
            'verifyPassword' => $verifyPassword,
            'getPasswordHash' => 'hash-of-new-password',
        ]);
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
                'getPersonById' => $targetUser,
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
            $this->makeEmpty(UserSettingsServiceInterface::class),
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

    private function createUpdateParams(
        bool $admin = false,
        string $email = 'test@example.com',
        ?string $welcomeScreenPath = null
    ): UpdateUserParameter {
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
            welcomeScreenPath: $welcomeScreenPath,
        );
    }

    private function createUserSettingsService(?string $expectedPath, User $expectedUser): UserSettingsServiceInterface
    {
        $update = $expectedPath === null
            ? Expected::never()
            : Expected::once(function (?string $path, UserInterface $user) use ($expectedPath, $expectedUser): void {
                $this->assertSame($expectedPath, $path);
                $this->assertSame($expectedUser, $user);
            });

        return $this->makeEmpty(UserSettingsServiceInterface::class, [
            'updateWelcomeScreenPath' => $update,
        ]);
    }

    private function createProfileParams(?string $welcomeScreenPath = null): UpdateUserProfile
    {
        return new UpdateUserProfile(
            firstname: 'Test',
            lastname: 'User',
            email: 'test@example.com',
            language: 'en',
            dateTimeLocale: null,
            welcomeScreen: true,
            memorizeTabs: false,
            contentLanguages: [],
            keyBindings: [],
            welcomeScreenPath: $welcomeScreenPath,
        );
    }

    private function createService(
        User $targetUser,
        UserInterface $currentUser,
        ?AuthenticationResolverInterface $authenticationResolver = null,
        ?ValidatorInterface $validator = null,
        ?UserSettingsServiceInterface $userSettingsService = null
    ): UserUpdateService {
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
            $authenticationResolver ?? $this->makeEmpty(AuthenticationResolverInterface::class),
            $this->makeEmpty(CacheResolverInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $currentUser,
            ]),
            $this->makeEmpty(UserRepositoryInterface::class, [
                'getPersonById' => $targetUser,
            ]),
            $updateService,
            $this->makeEmpty(UserPerspectiveServiceInterface::class),
            $userSettingsService ?? $this->makeEmpty(UserSettingsServiceInterface::class),
            $validator ?? $this->makeEmpty(ValidatorInterface::class),
        );
    }
}
