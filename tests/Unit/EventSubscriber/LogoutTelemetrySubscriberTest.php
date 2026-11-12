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
use Pimcore\Bundle\StudioBackendBundle\EventSubscriber\LogoutTelemetrySubscriber;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\LoginMarkerInterface;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\SessionLoginMarker;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Telemetry\TelemetryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use function array_keys;
use function time;

/**
 * @internal
 */
final class LogoutTelemetrySubscriberTest extends Unit
{
    /**
     * @var list<array{event: string, properties: array<string, mixed>}>
     */
    private array $captured = [];

    /**
     * Symfony's SessionLogoutListener invalidates the session at priority 0; the marker has to be read
     * before that, so the subscriber sits above it.
     */
    public function testSubscribesToLogoutAheadOfTheSessionInvalidation(): void
    {
        $events = LogoutTelemetrySubscriber::getSubscribedEvents();

        $this->assertSame(['onLogout', 16], $events[LogoutEvent::class] ?? null);
        $this->assertGreaterThan(0, $events[LogoutEvent::class][1]);
    }

    /**
     * Only the Studio logout route counts; other firewalls log out through the same event.
     */
    public function testIgnoresLogoutsOnUnrelatedRoutes(): void
    {
        $this->subscriber()->onLogout($this->event('some_other_firewall_logout', admin: true));
        $this->subscriber()->onLogout($this->event(null, admin: true));

        $this->assertSame([], $this->captured);
    }

    public function testCapturesExactlyOneEventPerStudioLogout(): void
    {
        $this->subscriber()->onLogout($this->event('pimcore_studio_api_logout', admin: true));

        $this->assertCount(1, $this->captured);
        $this->assertSame('studio.logout', $this->captured[0]['event']);
    }

    /**
     * Content-never: without a login marker the only thing that leaves is whether the account is an
     * admin - never a user id, name, email, role or session detail.
     */
    public function testCapturesOnlyTheIsAdminBooleanWithoutAMarker(): void
    {
        $this->subscriber()->onLogout($this->event('pimcore_studio_api_logout', admin: true));

        $this->assertSame(['is_admin'], array_keys($this->captured[0]['properties']));
        $this->assertTrue($this->captured[0]['properties']['is_admin']);
    }

    public function testReportsNonAdminUsersAsSuch(): void
    {
        $this->subscriber()->onLogout($this->event('pimcore_studio_api_logout', admin: false));

        $this->assertFalse($this->captured[0]['properties']['is_admin']);
    }

    /**
     * A logout without a token, or with something other than a Pimcore user, is still a logout - and not
     * an admin one.
     */
    public function testAMissingOrForeignUserIsNotTreatedAsAdmin(): void
    {
        $this->subscriber()->onLogout($this->event('pimcore_studio_api_logout', admin: null));
        $this->subscriber()->onLogout(new LogoutEvent($this->request('pimcore_studio_api_logout'), null));

        $this->assertCount(2, $this->captured);
        $this->assertFalse($this->captured[0]['properties']['is_admin']);
        $this->assertFalse($this->captured[1]['properties']['is_admin']);
    }

    /**
     * With the marker the login twin left behind, the event carries how long the session lasted.
     */
    public function testAddsTheSessionDurationFromTheLoginMarker(): void
    {
        $event = $this->event('pimcore_studio_api_logout', admin: false, withSession: true);
        $event->getRequest()->getSession()->set(LoginMarkerInterface::SESSION_KEY, time() - 3600);

        $this->subscriber()->onLogout($event);

        $properties = $this->captured[0]['properties'];
        $this->assertSame(['is_admin', 'session_duration_s'], array_keys($properties));
        $this->assertGreaterThanOrEqual(3600, $properties['session_duration_s']);
        $this->assertLessThanOrEqual(3602, $properties['session_duration_s']);
    }

    /**
     * A session without the marker (started before the deployment, or through another firewall sharing
     * the session context) reports no duration rather than a wrong one.
     */
    public function testLeavesTheDurationAbsentWithoutAMarker(): void
    {
        $this->subscriber()->onLogout($this->event('pimcore_studio_api_logout', admin: false, withSession: true));

        $this->assertArrayNotHasKey('session_duration_s', $this->captured[0]['properties']);
    }

    private function subscriber(): LogoutTelemetrySubscriber
    {
        $telemetry = $this->createStub(TelemetryInterface::class);
        $telemetry->method('capture')->willReturnCallback(
            function (string $event, array $properties = []): void {
                $this->captured[] = ['event' => $event, 'properties' => $properties];
            }
        );

        return new LogoutTelemetrySubscriber($telemetry, new SessionLoginMarker());
    }

    /**
     * @param bool|null $admin true/false for a Pimcore user, null for a non-Pimcore user object
     */
    private function event(?string $route, ?bool $admin, bool $withSession = false): LogoutEvent
    {
        if ($admin === null) {
            $user = $this->createStub(UserInterface::class);
        } else {
            $pimcoreUser = new User();
            $pimcoreUser->setAdmin($admin);
            $user = new SecurityUser($pimcoreUser);
        }

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $request = $this->request($route);
        if ($withSession) {
            $request->setSession(new Session(new MockArraySessionStorage()));
        }

        return new LogoutEvent($request, $token);
    }

    private function request(?string $route): Request
    {
        $request = new Request();
        if ($route !== null) {
            $request->attributes->set('_route', $route);
        }

        return $request;
    }
}
