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

namespace Pimcore\Bundle\StudioBackendBundle\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\Telemetry\LoginMarkerInterface;
use Pimcore\Security\User\User;
use Pimcore\Telemetry\TelemetryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Captures a content-never `studio.logout` event on each explicit Studio logout - the twin of
 * `LoginTelemetrySubscriber` (pimcore/product-management#1408, row 24: session length and how many
 * users end their session deliberately rather than letting it expire).
 *
 * Scoped to the Studio logout route (`pimcore_studio_api_logout`, the `logout` path of the Studio
 * firewall): other firewalls log out through the same Symfony event and are not Studio sessions. The
 * properties are whether the user was an admin and, when the login twin left its marker in the session,
 * how many seconds the session lasted - never a username, email, id or session detail. A logout without
 * a token (already expired session) is still a logout, and not an admin one.
 *
 * Runs above priority 0 because Symfony's SessionLogoutListener invalidates the session there, and
 * the login marker has to be read before that.
 *
 * @internal
 */
final readonly class LogoutTelemetrySubscriber implements EventSubscriberInterface
{
    private const EVENT_STUDIO_LOGOUT = 'studio.logout';

    private const LOGOUT_ROUTE = 'pimcore_studio_api_logout';

    private const PRIORITY_BEFORE_SESSION_INVALIDATION = 16;

    public function __construct(
        private TelemetryInterface $telemetry,
        private LoginMarkerInterface $loginMarker,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => ['onLogout', self::PRIORITY_BEFORE_SESSION_INVALIDATION],
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        if ($event->getRequest()->attributes->get('_route') !== self::LOGOUT_ROUTE) {
            return;
        }

        $properties = ['is_admin' => $this->isAdmin($event)];

        $duration = $this->loginMarker->sessionDurationSeconds($event->getRequest());
        if ($duration !== null) {
            $properties['session_duration_s'] = $duration;
        }

        $this->telemetry->capture(self::EVENT_STUDIO_LOGOUT, $properties);
    }

    private function isAdmin(LogoutEvent $event): bool
    {
        $user = $event->getToken()?->getUser();

        return $user instanceof User && $user->getUser()->isAdmin();
    }
}
