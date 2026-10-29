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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Service\Security;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Permission\ElementPermissionServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityService;
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\PendingSessionCheckerInterface;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Workflow\Manager;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * A login whose two-factor code is still pending is not a current user.
 */
final class SecurityServiceCurrentUserTest extends Unit
{
    public function testCompletedLoginIsTheCurrentUser(): void
    {
        $user = new User();
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken(new SecurityUser($user), 'pimcore_studio'));

        $this->assertSame($user, $this->service($tokenStorage, pending: false)->getCurrentUser());
    }

    /**
     * A request authenticated on its own (e.g. a personal access token) is not affected by a
     * pending login in the browser session it happens to carry.
     */
    public function testCompletedTokenWinsOverAPendingSession(): void
    {
        $user = new User();
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken(new SecurityUser($user), 'pimcore_mcp'));

        $this->assertSame($user, $this->service($tokenStorage, pending: true)->getCurrentUser());
    }

    public function testTokenWaitingForTheCodeIsNoUser(): void
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new TwoFactorToken(
            new UsernamePasswordToken(new SecurityUser(new User()), 'pimcore_studio'),
            null,
            'pimcore_studio',
            ['google']
        ));

        $this->expectException(UserNotFoundException::class);
        $this->service($tokenStorage, pending: false)->getCurrentUser();
    }

    /**
     * Outside the Studio firewall the session is read through core, which accepts a pending login.
     */
    public function testSessionWaitingForTheCodeIsNoUser(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->service(new TokenStorage(), pending: true)->getCurrentUser();
    }

    public function testCompletedSessionIsTheCurrentUser(): void
    {
        $user = new User();

        $service = $this->service(new TokenStorage(), pending: false, sessionUser: $user);

        $this->assertSame($user, $service->getCurrentUser());
    }

    private function service(TokenStorage $tokenStorage, bool $pending, ?User $sessionUser = null): SecurityService
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/'));

        return new SecurityService(
            $this->makeEmpty(ElementPermissionServiceInterface::class),
            $this->makeEmpty(AuthenticationResolverInterface::class, [
                'authenticateSession' => $sessionUser ?? new User(),
            ]),
            $tokenStorage,
            $this->makeEmpty(Manager::class),
            $this->makeEmpty(PendingSessionCheckerInterface::class, ['isCodePending' => $pending]),
            $requestStack,
        );
    }
}
