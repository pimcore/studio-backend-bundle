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

use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\OAuthPath;
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
        private readonly ScopeRegistryInterface $scopeRegistry,
        private readonly bool $clientIdMetadataDocumentSupported = false,
        private readonly bool $registrationEnabled = false,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        // Configuration requires a non-null issuer whenever the server is enabled, and
        // the endpoint guard 404s this route while it is off, so the fallback is only
        // reached by a container built past both. It must match what tokens are stamped
        // with; see Configuration::OAUTH_ISSUER_REQUIRED_ERROR.
        $base = $this->issuer ?? $request->getSchemeAndHttpHost();

        $metadata = [
            'issuer' => $base,
            'authorization_endpoint' => $base . OAuthPath::AUTHORIZE,
            'token_endpoint' => $base . OAuthPath::TOKEN,
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'response_types_supported' => ['code'],
            'code_challenge_methods_supported' => ['S256'],
            // `client_secret_post` is not advertised: the transport a client uses is not
            // distinguishable by the time this server sees the request, so it cannot be
            // enforced, and advertising a method a client may register but that means
            // nothing would be a promise this server does not keep. See
            // ClientRegistrar::AUTH_METHODS.
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'none'],
            'scopes_supported' => $this->scopeRegistry->all(),
            'authorization_response_iss_parameter_supported' => true,
            // CIMD: clients may present an HTTPS URL as client_id (no registration).
            'client_id_metadata_document_supported' => $this->clientIdMetadataDocumentSupported,
        ];

        // Only advertised when Dynamic Client Registration is enabled, so clients
        // that key off this field don't attempt to register when it is off.
        if ($this->registrationEnabled) {
            $metadata['registration_endpoint'] = $base . OAuthPath::REGISTER;
        }

        return new JsonResponse($metadata);
    }
}
