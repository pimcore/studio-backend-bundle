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

use const PHP_URL_PATH;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Resolver\RequestResourceResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use function is_string;
use function parse_url;
use function rtrim;
use function sprintf;

/**
 * Entry point for the MCP firewall: returns 401 for unauthenticated requests.
 * When the OAuth server is enabled it adds the RFC 9728 discovery challenge
 * (`WWW-Authenticate: Bearer resource_metadata="…", scope="mcp:read"`) pointing
 * at the protected-resource metadata; otherwise it returns a plain 401 so
 * behaviour is unchanged when OAuth is off.
 *
 * @internal
 */
final class McpAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private const string METADATA_PREFIX = '/.well-known/oauth-protected-resource';

    private const string DEFAULT_SCOPE = 'mcp:read';

    public function __construct(
        private readonly bool $oauthEnabled,
        private readonly RequestResourceResolverInterface $resourceResolver,
        private readonly ?string $issuer = null,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $response = new JsonResponse(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);

        if ($this->oauthEnabled) {
            $response->headers->set(
                'WWW-Authenticate',
                sprintf(
                    'Bearer resource_metadata="%s", scope="%s"',
                    $this->metadataUrl($request),
                    self::DEFAULT_SCOPE,
                )
            );
        }

        return $response;
    }

    /**
     * Points at the resource the authenticator would actually validate the token against,
     * not the raw request path. The resolver matches by longest prefix, so when a broader
     * resource covers this endpoint the two differ, and advertising the request path would
     * send the client to a metadata document the controller's exact lookup answers with a
     * 404. Falls back to the request path when nothing is registered for the endpoint.
     *
     * The base is the configured issuer, which is what the resource is registered under;
     * the request host would resolve to nothing whenever the two differ, as behind a proxy.
     */
    private function metadataUrl(Request $request): string
    {
        $base = rtrim($this->issuer ?? $request->getSchemeAndHttpHost(), '/');

        $resource = $this->resourceResolver->resolve($request);
        $resourcePath = $resource !== null ? parse_url($resource->canonicalUri, PHP_URL_PATH) : null;
        $path = is_string($resourcePath) && $resourcePath !== '' ? $resourcePath : $request->getPathInfo();

        return $base . self::METADATA_PREFIX . $path;
    }
}
