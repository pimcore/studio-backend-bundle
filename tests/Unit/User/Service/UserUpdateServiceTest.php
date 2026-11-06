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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdatePasswordParameter;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdateUserParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UpdateUserProfile;
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
            $this->makeEmpty(UserInterface::class, ['isAdmin' => false]),
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
            $this->makeEmpty(UserInterface::class, ['isAdmin' => false]),
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
            $this->makeEmpty(UserInterface::class, ['isAdmin' => false]),
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
            $this->makeEmpty(UserInterface::class, ['isAdmin' => false]),
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

    private function createTargetUser(bool $isAdmin): User
    {
        $user = new User();
        $user->setAdmin($isAdmin);
        $user->setActive(true);
        $user->setName('targetuser');

        return $user;
    }

    private function createUpdateParams(bool $admin = false, ?string $welcomeScreenPath = null): UpdateUserParameter
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
                'getUserById' => $targetUser,
            ]),
            $updateService,
            $this->makeEmpty(UserPerspectiveServiceInterface::class),
            $userSettingsService ?? $this->makeEmpty(UserSettingsServiceInterface::class),
            $validator ?? $this->makeEmpty(ValidatorInterface::class),
        );
    }
}
