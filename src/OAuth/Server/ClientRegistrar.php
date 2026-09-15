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

use const JSON_THROW_ON_ERROR;
use JsonException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\RegisteredClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\ClientRegistrationException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\DynamicClientStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\RedirectUriPolicy;
use function array_is_list;
use function array_unique;
use function array_values;
use function bin2hex;
use function hash;
use function in_array;
use function is_array;
use function is_string;
use function json_encode;
use function preg_split;
use function random_bytes;
use function sort;
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

    /**
     * Bump when the digested shape changes, so old and new digests cannot collide.
     */
    private const int DIGEST_VERSION = 1;

    public function __construct(
        private DynamicClientStoreInterface $store,
        private ScopeRegistryInterface $scopeRegistry,
    ) {
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @throws ClientRegistrationException
     * @throws JsonException
     */
    public function register(array $metadata): RegisteredClient
    {
        $redirectUris = $this->parseRedirectUris($metadata['redirect_uris'] ?? null);
        $authMethod = $this->parseAuthMethod($metadata['token_endpoint_auth_method'] ?? null);
        $grantTypes = $this->parseGrantTypes($metadata['grant_types'] ?? null);
        $scopes = $this->parseScopes($metadata['scope'] ?? null);
        $name = $this->parseName($metadata['client_name'] ?? null);

        $confidential = $authMethod !== 'none';
        $metadataHash = $this->metadataHash($name, $redirectUris, $grantTypes, $scopes, $authMethod);

        // Recognising a repeat registration keeps a well-behaved client that re-registers
        // on every start to a single row. It is not a defence against a hostile caller,
        // who controls every input to the digest and can make a new one with an extra
        // space; the rate limiter is what bounds that. Done unconditionally all the same,
        // and deliberately not gated on the rate limiter's enabled flag, so turning
        // throttling off does not also turn this off.
        if (!$confidential) {
            $existing = $this->store->findByMetadataHash($metadataHash);

            if ($existing !== null) {
                return $this->asRegisteredClient($existing, $authMethod);
            }
        }

        $identifier = 'dcr_' . bin2hex(random_bytes(16));

        $secret = null;
        $secretHash = null;
        if ($confidential) {
            $secret = bin2hex(random_bytes(32));
            // A plain SHA-256 rather than a password hash on purpose: the secret is a
            // 256-bit CSPRNG value this server generates, never a user-chosen one, so
            // there is no guessable keyspace for a rainbow table or a brute force to
            // work against and nothing for a salt or a work factor to buy. The
            // comparison is still constant-time (see ClientRepository::validateClient).
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
            // Only a public client is deduplicated, so only a public client stores a
            // digest to be matched on.
            $confidential ? null : $metadataHash,
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
     * Digest of the metadata the client chose, which is what decides whether two
     * registration requests describe the same client. The generated identifier and
     * secret are ours rather than the client's, so they are deliberately not part of it.
     *
     * The three lists are sorted first: RFC 7591 gives their order no meaning, so a
     * client listing the same redirect URIs in a different order has registered the same
     * client and must not get a second row for it.
     *
     * @param list<string> $redirectUris
     * @param list<string> $grantTypes
     * @param list<string> $scopes
     *
     * @throws JsonException
     */
    private function metadataHash(
        string $name,
        array $redirectUris,
        array $grantTypes,
        array $scopes,
        string $authMethod,
    ): string {
        sort($redirectUris);
        sort($grantTypes);
        sort($scopes);

        // JSON_THROW_ON_ERROR rather than casting the result: `(string) false` is the empty
        // string, so a silent encoding failure would hash every client to the same digest
        // and hand each one the previous client's client_id.
        //
        // The version tag makes a future change to this canonicalisation deliberate. Bumping
        // it moves every client to a new digest at once, which is a one-off wave of new rows
        // rather than the silent partial duplication of changing the shape in place.
        return hash('sha256', json_encode([
            'v' => self::DIGEST_VERSION,
            'client_name' => $name,
            'redirect_uris' => $redirectUris,
            'grant_types' => $grantTypes,
            'scope' => $scopes,
            'token_endpoint_auth_method' => $authMethod,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * The RFC 7591 response for a client that already existed. `client_secret` is absent
     * by construction: only public clients reach here, and a public client has none.
     * `client_id_issued_at` reports when the client was first registered rather than now,
     * so a repeat call does not claim to have created something.
     */
    private function asRegisteredClient(DynamicClient $client, string $authMethod): RegisteredClient
    {
        return new RegisteredClient(
            $client->identifier,
            $client->name,
            $client->redirectUris,
            $client->grantTypes,
            $client->scopes,
            $authMethod,
            null,
            $client->createdAt ?? time(),
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

        // De-duplicated for the same reason the digest sorts them: the set is what
        // identifies the client, so a repeated entry is the same client, not a new one.
        return array_values(array_unique($value));
    }

    private function assertValidRedirectUri(string $uri): void
    {
        if (RedirectUriPolicy::isAcceptable($uri)) {
            return;
        }

        throw new ClientRegistrationException(
            'invalid_redirect_uri',
            'redirect_uri must be an absolute URI without a fragment, using https or http on a loopback host.'
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

        // "mcp:read mcp:read" grants exactly what "mcp:read" does, so it must not register
        // as a different client.
        return array_values(array_unique($requested));
    }

    /**
     * Trimmed, not merely tested for being non-blank: the stored value is what the digest
     * is taken over, so returning it untrimmed made "Claude", " Claude" and "Claude " three
     * separate clients whose consent screens are indistinguishable.
     */
    private function parseName(mixed $value): string
    {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return 'Dynamically Registered Client';
    }
}
