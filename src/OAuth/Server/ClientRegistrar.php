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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\RegisteredClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\ClientRegistrationException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\DynamicClientStoreInterface;
use function array_is_list;
use function bin2hex;
use function hash;
use function in_array;
use function is_array;
use function is_string;
use function parse_url;
use function preg_split;
use function random_bytes;
use function str_contains;
use function strtolower;
use function time;
use function trim;

/**
 * Validates an RFC 7591 registration request and creates a public (PKCE) or
 * confidential client. Kept deliberately narrow: only the grants, scopes and
 * auth methods the embedded server actually supports are accepted.
 *
 * @internal
 */
final readonly class ClientRegistrar
{
    private const array SUPPORTED_GRANTS = ['authorization_code', 'refresh_token'];

    private const array AUTH_METHODS = ['none', 'client_secret_basic', 'client_secret_post'];

    private const array LOOPBACK_HOSTS = ['localhost', '127.0.0.1', '::1'];

    public function __construct(
        private DynamicClientStoreInterface $store,
        private ScopeRegistryInterface $scopeRegistry,
    ) {
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @throws ClientRegistrationException
     */
    public function register(array $metadata): RegisteredClient
    {
        $redirectUris = $this->parseRedirectUris($metadata['redirect_uris'] ?? null);
        $authMethod = $this->parseAuthMethod($metadata['token_endpoint_auth_method'] ?? null);
        $grantTypes = $this->parseGrantTypes($metadata['grant_types'] ?? null);
        $scopes = $this->parseScopes($metadata['scope'] ?? null);
        $name = $this->parseName($metadata['client_name'] ?? null);

        $confidential = $authMethod !== 'none';
        $identifier = 'dcr_' . bin2hex(random_bytes(16));

        $secret = null;
        $secretHash = null;
        if ($confidential) {
            $secret = bin2hex(random_bytes(32));
            $secretHash = hash('sha256', $secret);
        }

        $this->store->save(new DynamicClient(
            $identifier,
            $name,
            $redirectUris,
            $grantTypes,
            $scopes,
            $confidential,
            $secretHash,
        ));

        return new RegisteredClient(
            $identifier,
            $name,
            $redirectUris,
            $grantTypes,
            $scopes,
            $authMethod,
            $secret,
            time(),
        );
    }

    /**
     * @return list<string>
     */
    private function parseRedirectUris(mixed $value): array
    {
        if (!is_array($value) || $value === [] || !array_is_list($value)) {
            throw new ClientRegistrationException(
                'invalid_redirect_uri',
                'At least one redirect_uri is required.'
            );
        }

        foreach ($value as $uri) {
            if (!is_string($uri)) {
                throw new ClientRegistrationException('invalid_redirect_uri', 'Each redirect_uri must be a string.');
            }
            $this->assertValidRedirectUri($uri);
        }

        return $value;
    }

    private function assertValidRedirectUri(string $uri): void
    {
        $parts = parse_url($uri);
        if ($parts === false || !isset($parts['scheme'], $parts['host']) || str_contains($uri, '#')) {
            throw new ClientRegistrationException(
                'invalid_redirect_uri',
                'redirect_uri must be an absolute URI without a fragment.'
            );
        }

        $scheme = strtolower($parts['scheme']);
        if ($scheme === 'https') {
            return;
        }

        if ($scheme === 'http' && in_array(strtolower($parts['host']), self::LOOPBACK_HOSTS, true)) {
            return;
        }

        throw new ClientRegistrationException(
            'invalid_redirect_uri',
            'redirect_uri must use https, or http on a loopback host.'
        );
    }

    private function parseAuthMethod(mixed $value): string
    {
        // RFC 7591 default when omitted.
        $method = $value ?? 'client_secret_basic';
        if (!is_string($method) || !in_array($method, self::AUTH_METHODS, true)) {
            throw new ClientRegistrationException(
                'invalid_client_metadata',
                'Unsupported token_endpoint_auth_method.'
            );
        }

        return $method;
    }

    /**
     * @return list<string>
     */
    private function parseGrantTypes(mixed $value): array
    {
        if ($value === null) {
            return ['authorization_code'];
        }

        if (!is_array($value) || !array_is_list($value)) {
            throw new ClientRegistrationException('invalid_client_metadata', 'grant_types must be an array.');
        }

        foreach ($value as $grant) {
            if (!is_string($grant) || !in_array($grant, self::SUPPORTED_GRANTS, true)) {
                throw new ClientRegistrationException(
                    'invalid_client_metadata',
                    'Unsupported grant_type; only authorization_code and refresh_token are available.'
                );
            }
        }

        // The interactive flow is always required; keep refresh_token if requested.
        $grants = ['authorization_code'];
        if (in_array('refresh_token', $value, true)) {
            $grants[] = 'refresh_token';
        }

        return $grants;
    }

    /**
     * @return list<string>
     */
    private function parseScopes(mixed $value): array
    {
        // No scope requested means no scope. Defaulting to "the first registered scope"
        // would depend on bundle registration order, so the same client registration
        // would yield different scopes on different installations.
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_string($value)) {
            throw new ClientRegistrationException('invalid_client_metadata', 'scope must be a space-delimited string.');
        }

        $requested = preg_split('/\s+/u', trim($value)) ?: [];
        foreach ($requested as $scope) {
            if (!$this->scopeRegistry->has($scope)) {
                throw new ClientRegistrationException('invalid_client_metadata', 'Unsupported scope: ' . $scope);
            }
        }

        return $requested;
    }

    private function parseName(mixed $value): string
    {
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return 'Dynamically Registered Client';
    }
}
