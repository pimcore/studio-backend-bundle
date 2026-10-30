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

    private function createService(
        User $targetUser,
        UserInterface $currentUser,
        ?AuthenticationResolverInterface $authenticationResolver = null
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
            $this->makeEmpty(ValidatorInterface::class),
        );
    }
}
