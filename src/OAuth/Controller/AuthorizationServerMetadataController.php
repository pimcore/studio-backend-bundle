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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * RFC 8414 Authorization Server Metadata (GET /.well-known/oauth-authorization-server).
 * Advertises only what the server currently offers; the authorization endpoint
 * and PKCE metadata are added when the auth-code flow lands.
 *
 * @internal
 */
final class AuthorizationServerMetadataController
{
    public function __construct(
        private readonly ?string $issuer,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $base = $this->issuer ?? $request->getSchemeAndHttpHost();

        return new JsonResponse([
            'issuer' => $base,
            'token_endpoint' => $base . '/pimcore-oauth/token',
            'grant_types_supported' => ['client_credentials'],
            'token_endpoint_auth_methods_supported' => ['client_secret_post', 'client_secret_basic'],
            'scopes_supported' => ['mcp:read', 'mcp:write'],
            'authorization_response_iss_parameter_supported' => true,
        ]);
    }
}
