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

use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StudioBackendPathTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
 * OAuth endpoint is covered by {@see \Pimcore\Bundle\StudioBackendBundle\OAuth\OAuthPath}
 * without anyone having to remember to gate it, which is the mistake this guards against.
 *
 * Responds `404` rather than `403`, so a disabled server is indistinguishable from
 * one that was never built — the same shape the registration endpoint already uses
 * for its own sub-flag.
 *
 * While enabled, it also refuses the endpoints with a `500` when the issuer is not a
 * bare origin. A literal issuer is checked at build by OAuthIssuerPass; one taken from
 * an environment variable is only known here, and serving OAuth with it malformed would
 * issue tokens and metadata whose URIs look plausible and never match.
 *
 * @internal
 */
final readonly class OAuthEndpointGuardSubscriber implements EventSubscriberInterface
{
    use StudioBackendPathTrait;

    public function __construct(
        private bool $enabled = false,
        private string $apiPrefix = '',
        private ?string $issuer = null,
        private ?LoggerInterface $logger = null,
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
        if (!$event->isMainRequest()
            || !$this->isOAuthPath($this->routedPath($event->getRequest()), $this->apiPrefix)
        ) {
            return;
        }

        if (!$this->enabled) {
            $event->setResponse(new JsonResponse(['error' => 'not_found'], Response::HTTP_NOT_FOUND));

            return;
        }

        if ($this->issuer !== null && !CanonicalUri::isCanonicalOrigin($this->issuer)) {
            // The cause goes to the log, not to the public response.
            $this->logger?->error(
                'pimcore_studio_backend.oauth.issuer must be a bare origin such as "https://pimcore.example.com"'
                . ' (lowercase host, no trailing slash, no path), got "{issuer}". Refusing OAuth requests.',
                ['issuer' => $this->issuer],
            );
            $event->setResponse(new JsonResponse(['error' => 'server_error'], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }
}
