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
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use function in_array;

/**
 * Captures a content-never `studio.login_succeeded` event on each interactive Studio login
 * (PM question #23: how many logins, how often - a usage/churn/upsell signal).
 *
 * Scoped to the two Studio login routes - credentials (`json_login` at `pimcore_studio_api_login`)
 * and token (`pimcore_studio_api_token_login`). These are alternative methods on the stateful Studio
 * firewall, so exactly one event fires per login: subsequent requests restore the token from the
 * session without re-authenticating (no event), and MCP is a separate firewall. The only property
 * is whether the user is an admin - never a username, email, or id.
 *
 * It also leaves the login time in the session (see {@see LoginMarkerInterface}) so the logout twin can
 * report the session duration - before {@see SessionCloseSubscriber} closes the session for this request.
 *
 * @internal
 */
final readonly class LoginTelemetrySubscriber implements EventSubscriberInterface
{
    private const EVENT_STUDIO_LOGIN = 'studio.login_succeeded';

    private const LOGIN_ROUTES = [
        'pimcore_studio_api_login',
        'pimcore_studio_api_token_login',
    ];

    /**
     * SessionCloseSubscriber saves and closes the session on the same event at priority 0; writing the
     * marker after that would start the session again and hold its lock for the rest of the request.
     */
    private const PRIORITY_BEFORE_SESSION_CLOSE = 16;

    public function __construct(
        private TelemetryInterface $telemetry,
        private LoginMarkerInterface $loginMarker,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => ['onLoginSuccess', self::PRIORITY_BEFORE_SESSION_CLOSE],
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        if (!in_array($event->getRequest()->attributes->get('_route'), self::LOGIN_ROUTES, true)) {
            return;
        }

        $this->loginMarker->record($event->getRequest());

        $this->telemetry->capture(self::EVENT_STUDIO_LOGIN, [
            'is_admin' => $this->isAdmin($event),
        ]);
    }

    private function isAdmin(LoginSuccessEvent $event): bool
    {
        $user = $event->getAuthenticatedToken()->getUser();

        return $user instanceof User && $user->getUser()->isAdmin();
    }
}
