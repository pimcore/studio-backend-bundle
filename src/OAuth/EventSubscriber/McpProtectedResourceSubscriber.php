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

use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ResourceRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;
use Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\OAuthAccessTokenAuthenticator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Registers this bundle's own MCP endpoints as a protected resource, so the
 * authorization server can issue tokens for them out of the box.
 *
 * Without this the bundle registers no resource at all: an installation that enables
 * OAuth and the MCP firewall, exactly as documented, gets an authorization server
 * that refuses every request with "no protected resources configured" until someone
 * hand-writes the entry, and whose RFC 9728 metadata document 404s.
 *
 * Registered per request rather than at container build for two reasons.
 *
 * The URI has to be the one {@see OAuthAccessTokenAuthenticator::resourceUri()}
 * enforces, and that is derived from the incoming request. Deriving it from the same
 * place is what makes the two incapable of disagreeing; taking it from `oauth.issuer`
 * instead would drift apart the moment a reverse proxy presents a different host, and
 * the symptom would be valid tokens refused with nothing saying why.
 *
 * And the registry is consulted on requests that are not MCP requests at all: the
 * RFC 9728 metadata document is fetched on a `.well-known` path, and a requested
 * `resource` is validated on `/pimcore-oauth/authorize`. Registering only on MCP
 * paths would leave both of those unresolvable, so this runs on every main request.
 *
 * @internal
 */
final readonly class McpProtectedResourceSubscriber implements EventSubscriberInterface
{
    /**
     * The scopes the bundle's MCP servers use. Declared here rather than in a separate
     * catalogue: the resource that supports a scope is the thing that defines it.
     */
    private const array SCOPES = ['mcp:read', 'mcp:write'];

    public function __construct(
        private ResourceRegistryInterface $resourceRegistry,
        private bool $enabled = false,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Ahead of the firewall (8) and the router (32), so the resource is present
            // before the MCP authenticator validates an audience against it and before
            // any controller reads the registry.
            KernelEvents::REQUEST => ['onKernelRequest', 256],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $uri = CanonicalUri::canonicalize(
            $event->getRequest()->getSchemeAndHttpHost() . OAuthAccessTokenAuthenticator::MCP_RESOURCE_PATH
        );

        // An operator entry in `oauth.resources` for the same URI wins: it is seeded
        // into the registry when the container is built, and registering over it here
        // would silently replace a deliberate configuration with this default.
        if ($this->resourceRegistry->has($uri)) {
            return;
        }

        $this->resourceRegistry->register(new ProtectedResource($uri, self::SCOPES, []));
    }
}
