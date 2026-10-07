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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ProtectedResourceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;

/**
 * Declares this bundle's MCP endpoints as an OAuth protected resource, so the
 * authorization server can issue tokens for them out of the box.
 *
 * Without it the bundle contributes no resource at all: an installation that enables
 * OAuth and the MCP firewall, exactly as documented, gets an authorization server that
 * refuses every request with "no protected resources configured" until someone
 * hand-writes the entry, and whose RFC 9728 metadata document 404s.
 *
 * The URI is built from the configured `oauth.issuer`, never from the request. It is
 * tempting to derive it from `$request->getSchemeAndHttpHost()` so that it matches
 * whatever the MCP authenticator sees, but with `framework.trusted_hosts` unset - the
 * default - that is the caller's `Host` header. A resource named after it would let an
 * attacker declare their own host as an audience, obtain a token stamped with it, and
 * then pass the audience check by replaying the same spoofed header: the check would
 * compare the attacker's string against the attacker's own string. Both this and
 * {@see \Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\OAuthAccessTokenAuthenticator}
 * pin to the configured issuer, which agrees just as reliably and cannot be supplied by
 * the caller.
 *
 * The issuer is mandatory whenever `oauth.enabled` is true (enforced in
 * {@see \Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration}), so it is
 * always present when this is reachable. Yielding nothing when it is somehow absent
 * fails closed: an unregistered resource is refused at the authorization endpoint.
 *
 * @internal
 */
final readonly class ProtectedResourceProvider implements ProtectedResourceProviderInterface
{
    /**
     * The scopes the bundle's MCP servers use. Declared on the resource rather than in a
     * catalogue of their own: the resource that supports a scope is what defines it.
     */
    private const array SCOPES = ['mcp:read', 'mcp:write'];

    public function __construct(
        private bool $enabled = false,
        private ?string $issuer = null,
    ) {
    }

    public function resources(): iterable
    {
        if (!$this->enabled || $this->issuer === null) {
            return;
        }

        yield new ProtectedResource(
            CanonicalUri::canonicalize($this->issuer . McpPath::BASE),
            self::SCOPES,
            [$this->issuer],
        );
    }
}
