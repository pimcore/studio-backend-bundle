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

use Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\OAuthAccessTokenAuthenticator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use function sprintf;

/**
 * Entry point for the MCP firewall: returns 401 for unauthenticated requests.
 * When the OAuth server is enabled it adds the RFC 9728 discovery challenge
 * (`WWW-Authenticate: Bearer resource_metadata="…", scope="mcp:read"`) pointing
 * at the protected-resource metadata for the MCP base resource; otherwise it
 * returns a plain 401 so behaviour is unchanged when OAuth is off.
 *
 * @internal
 */
final class McpAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private const string METADATA_PREFIX = '/.well-known/oauth-protected-resource';

    private const string DEFAULT_SCOPE = 'mcp:read';

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
            $response->headers->set(
                'WWW-Authenticate',
                sprintf(
                    'Bearer resource_metadata="%s", scope="%s"',
                    $request->getSchemeAndHttpHost()
                        . self::METADATA_PREFIX
                        . OAuthAccessTokenAuthenticator::MCP_RESOURCE_PATH,
                    self::DEFAULT_SCOPE,
                )
            );
        }

        return $response;
    }
}
