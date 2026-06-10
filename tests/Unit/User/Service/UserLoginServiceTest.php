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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SettingsProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ResetPassword;
use Pimcore\Bundle\StudioBackendBundle\User\Service\MailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserLoginService;
use Pimcore\Model\Site;
use Pimcore\Model\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
final class UserLoginServiceTest extends Unit
{
    public function testResetPasswordAcceptsUrlMatchingRequestHost(): void
    {
        $service = $this->createService(
            requestHost: 'mysite.com',
            mainDomain: '',
            sites: [],
            userExists: true,
        );

        $resetPassword = new ResetPassword('testuser', 'https://mysite.com/reset-password/');

        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordRejectsUrl(): void
    {
        $service = $this->createService(
            requestHost: 'mysite.com',
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
            requestHost: 'api.internal',
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
            requestHost: 'admin.example.com',
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
            requestHost: 'admin.example.com',
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
            requestHost: 'mysite.com',
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
            requestHost: 'mysite.com',
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
            requestHost: 'mysite.com',
            mainDomain: 'mysite.com',
            sites: [],
        );

        $resetPassword = new ResetPassword('testuser', '/just/a/path');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid reset password URL provided');
        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordRejectsWhenNoRequestAndNoDomainConfig(): void
    {
        $service = $this->createService(
            requestHost: null,
            mainDomain: '',
            sites: [],
        );

        $resetPassword = new ResetPassword('testuser', 'https://anything.com/reset');

        $this->expectException(InvalidArgumentException::class);
        $service->resetPassword($resetPassword);
    }

    public function testResetPasswordWithLocalhostDev(): void
    {
        $service = $this->createService(
            requestHost: 'localhost',
            mainDomain: '',
            sites: [],
            userExists: true,
        );

        $resetPassword = new ResetPassword('testuser', 'http://localhost/pimcore-studio/reset-password/');

        $service->resetPassword($resetPassword);
    }

    private function createService(
        ?string $requestHost,
        string $mainDomain,
        array $sites,
        bool $userExists = false,
    ): UserLoginService {
        $requestStack = new RequestStack();
        if ($requestHost !== null) {
            $requestStack->push(Request::create('https://' . $requestHost . '/api/user/reset-password'));
        }

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
            $requestStack,
            $this->makeEmpty(SettingsProviderInterface::class, [
                'getSettings' => ['main_domain' => $mainDomain],
            ]),
            $this->makeEmpty(SiteRepositoryInterface::class, [
                'listSites' => $sites,
            ]),
        );
    }
}
