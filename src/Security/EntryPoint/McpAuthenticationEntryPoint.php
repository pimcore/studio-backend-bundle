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

namespace Pimcore\Bundle\StudioBackendBundle\Security\EntryPoint;

use Pimcore\Bundle\StudioBackendBundle\Mcp\McpPath;
use Pimcore\Bundle\StudioBackendBundle\OAuth\OAuthPath;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use function sprintf;

/**
 * Entry point for the MCP firewall: returns 401 for unauthenticated requests.
 * When the OAuth server is enabled it adds the RFC 9728 discovery challenge
 * (`WWW-Authenticate: Bearer resource_metadata="…"`) pointing at the
 * protected-resource metadata for the MCP base resource; otherwise it returns a
 * plain 401 so behaviour is unchanged when OAuth is off.
 *
 * @internal
 */
final class McpAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private const string METADATA_PREFIX = OAuthPath::PROTECTED_RESOURCE_METADATA;

    public function __construct(
        private readonly bool $oauthEnabled,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $response = new JsonResponse(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);

        if ($this->oauthEnabled) {
            // Name the resource the token is actually validated against, not the path
            // that happened to 401. MCP endpoints live at sub-paths of the base
            // (/pimcore-mcp/agent/..., /pimcore-mcp/<bundle>), while
            // OAuthAccessTokenAuthenticator validates every one of them against the base
            // alone; appending the request path would advertise a metadata document for
            // an unregistered resource, which the discovery endpoint answers with 404.
            //
            // The host comes from the request on purpose, unlike the audience the
            // authenticator checks. This is a discovery URL - where the client should
            // fetch metadata from - and ProtectedResourceMetadataController resolves the
            // resource from the request too, so the two agree and a proxied deployment
            // gets a reachable URL. Pinning it to oauth.issuer instead would hand clients
            // an address that need not be the one they reached us on. Nothing is
            // authorized on the strength of this value, so a spoofed Host only misdirects
            // the caller that sent it; behind a proxy, set trusted_proxies so the
            // forwarded scheme and host are honoured.
            // No `scope` parameter. RFC 6750 makes it optional, and the two reasons to
            // leave it out both hold here. It was a hard-coded `mcp:read`, which a
            // configured resource may not support at all once an operator overrides the
            // built-in entry and its scopes_supported, so it could name a scope that does
            // not exist. And the metadata document this challenge points at already
            // advertises scopes_supported, derived from the registry and therefore correct
            // by construction - duplicating it here can only contradict it.
            //
            // Nothing compares a granted scope against an operation at this resource server
            // either, so a scope hint would describe a boundary that is not enforced.
            $response->headers->set(
                'WWW-Authenticate',
                sprintf(
                    'Bearer resource_metadata="%s"',
                    $request->getSchemeAndHttpHost() . self::METADATA_PREFIX . McpPath::BASE,
                )
            );
        }

        return $response;
    }
}
