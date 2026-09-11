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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use function rtrim;
use function str_starts_with;

/**
 * Makes the embedded authorization server's endpoints disappear while
 * `oauth.enabled` is off.
 *
 * The OAuth routes are declared unconditionally (in `config/pimcore/routing.yaml`
 * and, for the consent API, by the attribute loader), so without this guard they
 * stay reachable on every installation that never opted into OAuth. That is not
 * merely untidy: with the feature off there is no key material, so
 * {@see \Pimcore\Bundle\StudioBackendBundle\OAuth\Server\AuthorizationServerFactory::create()}
 * throws and `/pimcore-oauth/token` answers `500` while `/pimcore-oauth/authorize`
 * raises an uncaught exception — on public, unauthenticated paths. Dynamic client
 * registration would likewise stay writable whenever its own sub-flag was left on,
 * since that controller only consults the sub-flag.
 *
 * Gating here rather than in each controller keeps the switch structural: a new
 * OAuth endpoint is covered by the path prefixes below without anyone having to
 * remember to gate it, which is the mistake this guards against.
 *
 * Responds `404` rather than `403`, so a disabled server is indistinguishable from
 * one that was never built — the same shape the registration endpoint already uses
 * for its own sub-flag.
 *
 * @internal
 */
final readonly class OAuthEndpointGuardSubscriber implements EventSubscriberInterface
{
    /**
     * Root-level OAuth paths. Fixed, unlike the Studio API prefix, so they are
     * matched literally.
     */
    private const array PATH_PREFIXES = [
        '/.well-known/oauth-',
        '/pimcore-oauth/',
    ];

    /** Consent API path below the (configurable) Studio API prefix. */
    private const string API_SUFFIX = '/oauth/';

    public function __construct(
        private bool $enabled = false,
        private string $apiPrefix = '',
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Ahead of RouterListener (32), so a disabled endpoint is refused before
            // routing and the firewall ever look at it. Above OAuthCorsSubscriber
            // (250) as well, so a disabled server does not answer preflights for
            // endpoints it will not serve — that subscriber is inert when disabled
            // anyway, this only makes the ordering explicit.
            KernelEvents::REQUEST => ['onKernelRequest', 251],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->enabled || !$event->isMainRequest()) {
            return;
        }

        if (!$this->isOAuthPath($event->getRequest()->getPathInfo())) {
            return;
        }

        $event->setResponse(new JsonResponse(['error' => 'not_found'], Response::HTTP_NOT_FOUND));
    }

    private function isOAuthPath(string $pathInfo): bool
    {
        foreach (self::PATH_PREFIXES as $prefix) {
            if (str_starts_with($pathInfo, $prefix)) {
                return true;
            }
        }

        // Empty while the Studio API prefix is unset, which must not turn into a
        // bare "/oauth/" prefix that could belong to the host application.
        if ($this->apiPrefix === '') {
            return false;
        }

        return str_starts_with($pathInfo, rtrim($this->apiPrefix, '/') . self::API_SUFFIX);
    }
}
