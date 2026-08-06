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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\EventSubscriber;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\EventSubscriber\LoginTelemetrySubscriber;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Telemetry\TelemetryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @internal
 */
final class LoginTelemetrySubscriberTest extends Unit
{
    /**
     * @var list<array{event: string, properties: array<string, mixed>}>
     */
    private array $captured = [];

    public function testSubscribesToLoginSuccess(): void
    {
        $this->assertSame(
            [LoginSuccessEvent::class => 'onLoginSuccess'],
            LoginTelemetrySubscriber::getSubscribedEvents()
        );
    }

    /**
     * The Studio firewall is stateful and MCP is a separate firewall, so scoping to the two login
     * routes is what keeps this to exactly one event per login rather than one per request.
     */
    public function testIgnoresLoginsOnUnrelatedRoutes(): void
    {
        $this->subscriber()->onLoginSuccess($this->event('some_other_firewall_login', admin: true));
        $this->subscriber()->onLoginSuccess($this->event(null, admin: true));

        $this->assertSame([], $this->captured);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function studioLoginRoutes(): iterable
    {
        yield 'credentials login' => ['pimcore_studio_api_login'];
        yield 'token login' => ['pimcore_studio_api_token_login'];
    }

    /**
     * @dataProvider studioLoginRoutes
     */
    public function testCapturesExactlyOneEventPerStudioLogin(string $route): void
    {
        $this->subscriber()->onLoginSuccess($this->event($route, admin: true));

        $this->assertCount(1, $this->captured);
        $this->assertSame('studio.login_succeeded', $this->captured[0]['event']);
    }

    /**
     * Content-never: the only thing that may leave the instance is whether the account is an admin -
     * never a user id, name, email, role or session detail.
     */
    public function testCapturesOnlyTheIsAdminBoolean(): void
    {
        $this->subscriber()->onLoginSuccess($this->event('pimcore_studio_api_login', admin: true));

        $this->assertSame(['is_admin'], array_keys($this->captured[0]['properties']));
        $this->assertTrue($this->captured[0]['properties']['is_admin']);
    }

    public function testReportsNonAdminUsersAsSuch(): void
    {
        $this->subscriber()->onLoginSuccess($this->event('pimcore_studio_api_login', admin: false));

        $this->assertFalse($this->captured[0]['properties']['is_admin']);
    }

    /**
     * A token that carries something other than a Pimcore user must not be reported as an admin.
     */
    public function testAForeignUserObjectIsNotTreatedAsAdmin(): void
    {
        $this->subscriber()->onLoginSuccess($this->event('pimcore_studio_api_login', admin: null));

        $this->assertFalse($this->captured[0]['properties']['is_admin']);
    }

    private function subscriber(): LoginTelemetrySubscriber
    {
        $telemetry = $this->createMock(TelemetryInterface::class);
        $telemetry->method('capture')->willReturnCallback(
            function (string $event, array $properties = []): void {
                $this->captured[] = ['event' => $event, 'properties' => $properties];
            }
        );

        return new LoginTelemetrySubscriber($telemetry);
    }

    /**
     * @param bool|null $admin true/false for a Pimcore user, null for a non-Pimcore user object
     */
    private function event(?string $route, ?bool $admin): LoginSuccessEvent
    {
        $request = new Request();
        if ($route !== null) {
            $request->attributes->set('_route', $route);
        }

        if ($admin === null) {
            $user = $this->createMock(UserInterface::class);
        } else {
            // Pimcore\Model\User is final, and both types are cheap value-ish objects, so build
            // them for real rather than doubling.
            $pimcoreUser = new User();
            $pimcoreUser->setAdmin($admin);
            $user = new SecurityUser($pimcoreUser);
        }

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return new LoginSuccessEvent(
            $this->createMock(AuthenticatorInterface::class),
            $this->createMock(Passport::class),
            $token,
            $request,
            null,
            'pimcore_studio'
        );
    }
}
