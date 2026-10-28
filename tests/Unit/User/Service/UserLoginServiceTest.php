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
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Repository\SiteRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SettingsProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ResetPassword;
use Pimcore\Bundle\StudioBackendBundle\User\Service\MailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserLoginService;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPermissionServiceInterface;
use Pimcore\Model\Site;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionNamedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
final class UserLoginServiceTest extends Unit
{
    public function testResetPasswordAcceptsMixedCaseScheme(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            userExists: true,
        );

        $resetPassword = new ResetPassword('testuser', 'HTTPS://mysite.com/reset-password/');

        $service->resetPassword($resetPassword);
    }

    public function testServiceDoesNotDependOnTheCurrentRequest(): void
    {
        $constructor = (new ReflectionClass(UserLoginService::class))->getConstructor();
        self::assertNotNull($constructor);

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : '';

            self::assertNotContains(
                $typeName,
                [Request::class, RequestStack::class],
                'The reset password URL must be validated against configuration only, never the incoming request.'
            );
        }
    }

    public function testResetPasswordRejectsUrl(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
        );

        $resetPassword = new ResetPassword('testuser', 'https://unknown.com/capture');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reset password URL domain does not match any trusted domain');
        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordAcceptsMainDomain(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            userExists: true,
        );

        $resetPassword = new ResetPassword('testuser', 'https://mysite.com/reset-password/');

        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordAcceptsSiteMainDomain(): void
    {
        $site = new Site();
        $site->setMainDomain('shop.example.com');
        $site->setDomains([]);

        $service = $this->createService(
            mainDomain: 'admin.example.com',
            sites: [$site],
            userExists: true,
        );

        $resetPassword = new ResetPassword('testuser', 'https://shop.example.com/reset-password/');

        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordAcceptsSiteAliasDomain(): void
    {
        $site = new Site();
        $site->setMainDomain('shop.example.com');
        $site->setDomains(['www.shop.example.com', 'store.example.com']);

        $service = $this->createService(
            mainDomain: 'admin.example.com',
            sites: [$site],
            userExists: true,
        );

        $resetPassword = new ResetPassword('testuser', 'https://store.example.com/reset-password/');

        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordRejectsUnregisteredDomain(): void
    {
        $site = new Site();
        $site->setMainDomain('shop.example.com');
        $site->setDomains(['www.shop.example.com']);

        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [$site],
        );

        $resetPassword = new ResetPassword('testuser', 'https://unknown.com/reset');

        $this->expectException(InvalidArgumentException::class);
        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordRejectsMalformedUrl(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
        );

        $resetPassword = new ResetPassword('testuser', 'not-a-url');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid reset password URL provided');
        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordRejectsUrlWithoutHost(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
        );

        $resetPassword = new ResetPassword('testuser', '/just/a/path');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid reset password URL provided');
        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordRejectsNonHttpScheme(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
        );

        $resetPassword = new ResetPassword('testuser', 'ftp://mysite.com/reset');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid reset password URL provided');
        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordRejectsUrlWithCredentials(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
        );

        $resetPassword = new ResetPassword('testuser', 'https://other.example@mysite.com/reset');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid reset password URL provided');
        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordRejectsUrlWithBackslash(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
        );

        $resetPassword = new ResetPassword('testuser', 'https://other.example\\@mysite.com/reset');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid reset password URL provided');
        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordRejectsUrlWithWhitespaceOrControlCharacters(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
        );

        $resetPassword = new ResetPassword('testuser', "https://mysite.com/reset\t\n");

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid reset password URL provided');
        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordRejectsWhenNoDomainConfig(): void
    {
        $service = $this->createService(
            mainDomain: '',
            sites: [],
        );

        $resetPassword = new ResetPassword('testuser', 'https://anything.com/reset');

        $this->expectException(InvalidArgumentException::class);
        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordAcceptsConfiguredLocalhostDev(): void
    {
        $service = $this->createService(
            mainDomain: 'localhost',
            sites: [],
            userExists: true,
        );

        $resetPassword = new ResetPassword('testuser', 'http://localhost/pimcore-studio/reset-password/');

        $service->resetPassword($resetPassword);
    }

    public function testGetLoginTokenUrlDoesNotAcceptAnyUrlParameter(): void
    {
        $method = (new ReflectionClass(UserLoginService::class))->getMethod('getLoginTokenUrl');

        $this->assertCount(1, $method->getParameters());
        $this->assertSame('id', $method->getParameters()[0]->getName());
    }

    public function testGetLoginTokenUrlRejectsTargetWithPermissionCallerDoesNotHold(): void
    {
        $targetUser = $this->createTargetUser(false);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
            'isAllowed' => false,
        ]);
        $service = $this->createLoginTokenService($targetUser, $currentUser, ['assets']);

        $this->expectException(ForbiddenException::class);
        $service->getLoginTokenUrl(42);
    }

    public function testGetLoginTokenUrlAllowsTargetWithPermissionsCallerAlsoHolds(): void
    {
        $targetUser = $this->createTargetUser(false);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
            'isAllowed' => static fn (string $permission): bool => $permission === 'assets',
        ]);
        $service = $this->createLoginTokenService($targetUser, $currentUser, ['assets']);

        $url = $service->getLoginTokenUrl(42);

        $this->assertSame('https://mysite.com/login?token=test-token-123', $url);
    }

    public function testGetLoginTokenUrlAllowsAdminCallerRegardlessOfTargetPermissions(): void
    {
        $targetUser = $this->createTargetUser(false);
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => true,
        ]);
        $service = $this->createLoginTokenService($targetUser, $currentUser, ['assets', 'documents', 'seo']);

        $url = $service->getLoginTokenUrl(42);

        $this->assertSame('https://mysite.com/login?token=test-token-123', $url);
    }

    private function createTargetUser(bool $isAdmin, string $password = 'hashed-password'): User
    {
        $user = new User();
        $user->setAdmin($isAdmin);
        $user->setPassword($password);

        return $user;
    }

    private function createLoginTokenService(
        UserInterface $targetUser,
        UserInterface $currentUser,
        array $targetPermissions,
    ): UserLoginService {
        return new UserLoginService(
            $this->makeEmpty(AuthenticationResolverInterface::class, [
                'generateTokenByUser' => 'test-token-123',
            ]),
            $this->makeEmpty(MailServiceInterface::class),
            $this->makeEmpty(LoggerInterface::class),
            $this->makeEmpty(UrlGeneratorInterface::class, [
                'generate' => 'https://mysite.com/login?token=test-token-123',
            ]),
            $this->makeEmpty(UserRepositoryInterface::class, [
                'getUserById' => $targetUser,
            ]),
            $this->makeEmpty(UserResolverInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $currentUser,
            ]),
            $this->makeEmpty(SettingsProviderInterface::class),
            $this->makeEmpty(SiteRepositoryInterface::class),
            $this->makeEmpty(UserPermissionServiceInterface::class, [
                'getAllowedUserPermissions' => $targetPermissions,
            ]),
        );
    }

    private function createService(
        string $mainDomain,
        array $sites,
        bool $userExists = false,
    ): UserLoginService {
        $user = null;
        if ($userExists) {
            $user = new User();
            $user->setName('testuser');
            $user->setEmail('test@example.com');
            $user->setActive(true);
            $user->setPassword('hashed-password');
        }

        return new UserLoginService(
            $this->makeEmpty(AuthenticationResolverInterface::class, [
                'generateTokenByUser' => 'test-token-123',
            ]),
            $this->makeEmpty(MailServiceInterface::class),
            $this->makeEmpty(LoggerInterface::class),
            $this->makeEmpty(UrlGeneratorInterface::class),
            $this->makeEmpty(UserRepositoryInterface::class),
            $this->makeEmpty(UserResolverInterface::class, [
                'getByName' => $user,
            ]),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(SettingsProviderInterface::class, [
                'getSettings' => ['main_domain' => $mainDomain],
            ]),
            $this->makeEmpty(SiteRepositoryInterface::class, [
                'listSites' => $sites,
            ]),
            $this->makeEmpty(UserPermissionServiceInterface::class),
        );
    }
}
