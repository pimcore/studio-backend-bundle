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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StudioBackendPathTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use function in_array;

/**
 * CORS for the embedded authorization server's browser-facing endpoints.
 *
 * Browser-based MCP/OAuth clients (e.g. the MCP Inspector) run discovery and the
 * code->token exchange as cross-origin fetches, so the authorization-server
 * metadata (RFC 8414), protected-resource metadata (RFC 9728), token and
 * registration endpoints must send CORS headers or the browser blocks the client
 * from reading the responses.
 *
 * Unlike {@see \Pimcore\Bundle\StudioBackendBundle\EventSubscriber\CorsSubscriber}
 * for the cookie-authenticated Studio API, these endpoints are public and
 * cookie-less (Bearer/PKCE), so credentials are never sent and a wildcard origin
 * is used by default; an allow-list may be configured to restrict it. Credentials
 * are never enabled, so both "*" and the allow-list stay CORS-valid.
 *
 * Scoped to the authorization server's own root-level endpoints
 * ({@see \Pimcore\Bundle\StudioBackendBundle\OAuth\OAuthPath::ROOT_PREFIXES}) and gated on
 * `oauth.enabled`, so it is inert everywhere else and when the server is off. The consent
 * API is deliberately not covered: it lives under the Studio API prefix, it is
 * cookie-authenticated, and {@see \Pimcore\Bundle\StudioBackendBundle\EventSubscriber\CorsSubscriber}
 * already answers for it with the credentialed policy such an endpoint needs. Widening the
 * wildcard here to reach it would be the one change to avoid.
 *
 * @internal
 */
final readonly class OAuthCorsSubscriber implements EventSubscriberInterface
{
    use StudioBackendPathTrait;

    private const string ALLOW_METHODS = 'GET, POST, OPTIONS';

    private const string ALLOW_HEADERS = 'Authorization, Content-Type, Accept, MCP-Protocol-Version';

    /**
     * @param list<string> $allowedOrigins empty = any origin (wildcard)
     */
    public function __construct(
        private bool $enabled,
        private array $allowedOrigins = [],
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Before RouterListener (priority 32) so the OPTIONS preflight is
            // answered even though the routes only declare GET/POST.
            KernelEvents::REQUEST => ['onKernelRequest', 250],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->getMethod() !== 'OPTIONS' || !$this->isOAuthPath($this->routedPath($request))) {
            return;
        }

        $response = new Response(status: Response::HTTP_NO_CONTENT);
        $response->headers->set('Access-Control-Allow-Methods', self::ALLOW_METHODS);
        $response->headers->set('Access-Control-Allow-Headers', self::ALLOW_HEADERS);
        $response->headers->set('Access-Control-Max-Age', '3600');
        $this->applyAllowOrigin($request, $response);

        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isOAuthPath($this->routedPath($request))) {
            return;
        }

        $this->applyAllowOrigin($request, $event->getResponse());
    }

    private function applyAllowOrigin(Request $request, Response $response): void
    {
        $origin = $this->resolveAllowedOrigin($request);
        if ($origin === null) {
            return;
        }

        $response->headers->set('Access-Control-Allow-Origin', $origin);
        // Lets clients read the RFC 9728 challenge when it is served cross-origin.
        $response->headers->set('Access-Control-Expose-Headers', 'WWW-Authenticate');
        if ($origin !== '*') {
            $response->headers->set('Vary', 'Origin');
        }
    }

    private function resolveAllowedOrigin(Request $request): ?string
    {
        if ($this->allowedOrigins === []) {
            return '*';
        }

        $origin = $request->headers->get('Origin');

        return $origin !== null && in_array($origin, $this->allowedOrigins, true) ? $origin : null;
    }
}
