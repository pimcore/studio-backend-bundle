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
 * Advertises the authorization and token endpoints, the supported grants, the
 * S256 PKCE method, and the token-endpoint auth methods (including `none` for
 * public PKCE clients).
 *
 * @internal
 */
final class AuthorizationServerMetadataController
{
    public function __construct(
        private readonly ?string $issuer,
        private readonly bool $clientIdMetadataDocumentSupported = false,
        private readonly bool $registrationEnabled = false,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $base = $this->issuer ?? $request->getSchemeAndHttpHost();

        $metadata = [
            'issuer' => $base,
            'authorization_endpoint' => $base . '/pimcore-oauth/authorize',
            'token_endpoint' => $base . '/pimcore-oauth/token',
            'grant_types_supported' => ['authorization_code', 'client_credentials', 'refresh_token'],
            'response_types_supported' => ['code'],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['client_secret_post', 'client_secret_basic', 'none'],
            'scopes_supported' => ['mcp:read', 'mcp:write'],
            'authorization_response_iss_parameter_supported' => true,
            // CIMD: clients may present an HTTPS URL as client_id (no registration).
            'client_id_metadata_document_supported' => $this->clientIdMetadataDocumentSupported,
        ];

        // Only advertised when Dynamic Client Registration is enabled, so clients
        // that key off this field don't attempt to register when it is off.
        if ($this->registrationEnabled) {
            $metadata['registration_endpoint'] = $base . '/pimcore-oauth/register';
        }

        return new JsonResponse($metadata);
    }
}
