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

use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\ClientRegistrationException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\ClientRegistrar;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function implode;
use function is_array;
use function json_decode;

/**
 * RFC 7591 Dynamic Client Registration endpoint (POST /pimcore-oauth/register).
 * Open (unauthenticated) so a client with no prior credentials can register
 * itself, then run the authorization-code + PKCE flow. Gated on the
 * `oauth.dynamic_client_registration.enabled` flag: returns 404 when off.
 *
 * @internal
 */
final class ClientRegistrationController
{
    public function __construct(
        private readonly ClientRegistrar $registrar,
        private readonly bool $enabled,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->enabled) {
            return new JsonResponse(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent() ?: 'null', true);
        if (!is_array($payload)) {
            return new JsonResponse(
                ['error' => 'invalid_client_metadata', 'error_description' => 'Request body must be a JSON object.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $client = $this->registrar->register($payload);
        } catch (ClientRegistrationException $exception) {
            return new JsonResponse(
                ['error' => $exception->getError(), 'error_description' => $exception->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $body = [
            'client_id' => $client->identifier,
            'client_id_issued_at' => $client->issuedAt,
            'client_name' => $client->name,
            'redirect_uris' => $client->redirectUris,
            'grant_types' => $client->grantTypes,
            'response_types' => ['code'],
            'token_endpoint_auth_method' => $client->tokenEndpointAuthMethod,
            'scope' => implode(' ', $client->scopes),
        ];

        if ($client->secret !== null) {
            $body['client_secret'] = $client->secret;
            // 0 = the secret does not expire (RFC 7591 §3.2.1).
            $body['client_secret_expires_at'] = 0;
        }

        return new JsonResponse($body, Response::HTTP_CREATED);
    }
}
