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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\Invitation;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ResetPassword;
use Pimcore\Bundle\StudioBackendBundle\User\Service\MailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserLoginService;
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

    public function testSendInvitationAssignsAPasswordWhenTheUserHasNone(): void
    {
        $calls = [];
        $target = $this->createTargetUser(password: null);
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            targetUser: $target,
            calls: $calls,
        );

        $service->sendInvitation(7, new Invitation('https://mysite.com/pimcore-studio/reset-password/'));

        self::assertNotEmpty($target->getPassword(), 'A user without a password must be given one.');
        self::assertSame(32, strlen((string) $target->getPassword()));

        // the placeholder has to reach the database before the token is generated: the token is
        // only accepted for a user that has a password
        self::assertSame(
            ['updateUser:' . $target->getPassword(), 'generateToken'],
            $calls
        );
    }

    public function testSendInvitationKeepsAnExistingPassword(): void
    {
        $calls = [];
        $target = $this->createTargetUser(password: 'hashed-password');
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            targetUser: $target,
            calls: $calls,
        );

        $service->sendInvitation(7, new Invitation('https://mysite.com/pimcore-studio/reset-password/'));

        self::assertSame('hashed-password', $target->getPassword());
        self::assertSame(['generateToken'], $calls, 'A user that has a password must not be saved.');
    }

    public function testSendInvitationRefusesAnAdminTargetForANonAdmin(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            targetUser: $this->createTargetUser(password: 'hashed-password', admin: true),
            currentUserIsAdmin: false,
        );

        $this->expectException(ForbiddenException::class);

        $service->sendInvitation(7, new Invitation('https://mysite.com/pimcore-studio/reset-password/'));
    }

    public function testSendInvitationRejectsAnUntrustedInvitationUrl(): void
    {
        $sentUrls = [];
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            targetUser: $this->createTargetUser(password: 'hashed-password'),
            sentUrls: $sentUrls,
        );

        try {
            $service->sendInvitation(7, new Invitation('https://attacker.example/pimcore-studio/reset-password/'));
            self::fail('An invitation URL outside the configured domains must be rejected.');
        } catch (InvalidArgumentException) {
            self::assertSame([], $sentUrls, 'No mail may be sent for an untrusted invitation URL.');
        }
    }

    public function testSendInvitationRejectsAUserWithoutAnEmail(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            targetUser: $this->createTargetUser(password: 'hashed-password', email: null),
        );

        $this->expectException(InvalidArgumentException::class);

        $service->sendInvitation(7, new Invitation('https://mysite.com/pimcore-studio/reset-password/'));
    }

    public function testSendInvitationRejectsAMalformedEmail(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            targetUser: $this->createTargetUser(password: 'hashed-password', email: 'not-an-email'),
        );

        $this->expectException(InvalidArgumentException::class);

        $service->sendInvitation(7, new Invitation('https://mysite.com/pimcore-studio/reset-password/'));
    }

    public function testSendInvitationRejectsAnInactiveUser(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            targetUser: $this->createTargetUser(password: 'hashed-password', active: false),
        );

        $this->expectException(InvalidArgumentException::class);

        $service->sendInvitation(7, new Invitation('https://mysite.com/pimcore-studio/reset-password/'));
    }

    public function testSendInvitationSendsTheTokenUrl(): void
    {
        $sentUrls = [];
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            targetUser: $this->createTargetUser(password: 'hashed-password'),
            sentUrls: $sentUrls,
        );

        $service->sendInvitation(7, new Invitation('https://mysite.com/pimcore-studio/reset-password/'));

        self::assertSame(
            ['https://mysite.com/pimcore-studio/reset-password/?token=test-token-123'],
            $sentUrls
        );
    }

    public function testSendInvitationAppendsTheTokenToAUrlThatAlreadyHasAQuery(): void
    {
        $sentUrls = [];
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            targetUser: $this->createTargetUser(password: 'hashed-password'),
            sentUrls: $sentUrls,
        );

        $service->sendInvitation(7, new Invitation('https://mysite.com/pimcore-studio/reset-password/?lang=en'));

        self::assertSame(
            ['https://mysite.com/pimcore-studio/reset-password/?lang=en&token=test-token-123'],
            $sentUrls
        );
    }

    public function testSendInvitationPutsTheTokenBeforeAFragment(): void
    {
        $sentUrls = [];
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            targetUser: $this->createTargetUser(password: 'hashed-password'),
            sentUrls: $sentUrls,
        );

        $service->sendInvitation(7, new Invitation('https://mysite.com/pimcore-studio/reset-password/?lang=en#form'));

        self::assertSame(
            ['https://mysite.com/pimcore-studio/reset-password/?lang=en&token=test-token-123#form'],
            $sentUrls
        );
    }

    public function testSendInvitationReplacesAStaleTokenParameter(): void
    {
        $sentUrls = [];
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            targetUser: $this->createTargetUser(password: 'hashed-password'),
            sentUrls: $sentUrls,
        );

        $service->sendInvitation(7, new Invitation('https://mysite.com/pimcore-studio/reset-password/?token=stale'));

        self::assertSame(
            ['https://mysite.com/pimcore-studio/reset-password/?token=test-token-123'],
            $sentUrls
        );
    }

    public function testSendInvitationPropagatesMailFailures(): void
    {
        $service = $this->createService(
            mainDomain: 'mysite.com',
            sites: [],
            targetUser: $this->createTargetUser(password: 'hashed-password'),
            mailFails: true,
        );

        $this->expectException(SendMailException::class);

        $service->sendInvitation(7, new Invitation('https://mysite.com/pimcore-studio/reset-password/'));
    }

    private function createTargetUser(
        ?string $password,
        ?string $email = 'invitee@example.com',
        bool $active = true,
        bool $admin = false,
    ): User {
        $user = new User();
        $user->setId(7);
        $user->setName('invitee');
        $user->setActive($active);
        $user->setAdmin($admin);

        if ($email !== null) {
            $user->setEmail($email);
        }

        if ($password !== null) {
            $user->setPassword($password);
        }

        return $user;
    }

    private function createService(
        string $mainDomain,
        array $sites,
        bool $userExists = false,
        ?User $targetUser = null,
        bool $currentUserIsAdmin = true,
        array &$sentUrls = [],
        bool $mailFails = false,
        array &$calls = [],
    ): UserLoginService {
        $user = null;
        if ($userExists) {
            $user = new User();
            $user->setName('testuser');
            $user->setEmail('test@example.com');
            $user->setActive(true);
            $user->setPassword('hashed-password');
        }

        $currentUser = new User();
        $currentUser->setId(1);
        $currentUser->setName('current');
        $currentUser->setAdmin($currentUserIsAdmin);

        return new UserLoginService(
            $this->makeEmpty(AuthenticationResolverInterface::class, [
                'generateTokenByUser' => static function () use (&$calls): string {
                    $calls[] = 'generateToken';

                    return 'test-token-123';
                },
            ]),
            $this->makeEmpty(MailServiceInterface::class, [
                'sendInvitationMail' => static function (
                    UserInterface $invitee,
                    string $invitationUrl
                ) use (&$sentUrls, $mailFails): void {
                    if ($mailFails) {
                        throw new SendMailException('transport down');
                    }

                    $sentUrls[] = $invitationUrl;
                },
            ]),
            $this->makeEmpty(LoggerInterface::class),
            $this->makeEmpty(UrlGeneratorInterface::class),
            $this->makeEmpty(UserRepositoryInterface::class, [
                'getUserById' => $targetUser,
                'updateUser' => static function (UserInterface $saved) use (&$calls): void {
                    $calls[] = 'updateUser:' . $saved->getPassword();
                },
            ]),
            $this->makeEmpty(UserResolverInterface::class, [
                'getByName' => $user,
            ]),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $currentUser,
            ]),
            $this->makeEmpty(SettingsProviderInterface::class, [
                'getSettings' => ['main_domain' => $mainDomain],
            ]),
            $this->makeEmpty(SiteRepositoryInterface::class, [
                'listSites' => $sites,
            ]),
        );
    }
}
