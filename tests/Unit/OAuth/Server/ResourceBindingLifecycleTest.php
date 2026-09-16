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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Server;

use const OPENSSL_KEYTYPE_RSA;
use const PHP_URL_QUERY;
use Codeception\Test\Unit;
use DateInterval;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\UnencryptedToken;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Pimcore\Bundle\StudioBackendBundle\Entity\OAuth\OAuthTokenRecord;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ConfigProtectedResourceRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ScopeRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\UserEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Grant\LoopbackAuthCodeGrant;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Grant\ResourceRefreshTokenGrant;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\AccessTokenRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\AuthCodeRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\RefreshTokenRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ScopeRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\TokenRecordStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\ResponseType\ScopedBearerTokenResponse;
use Psr\Http\Message\ServerRequestInterface;
use function base64_encode;
use function hash;
use function json_decode;
use function openssl_pkey_export;
use function openssl_pkey_get_details;
use function openssl_pkey_new;
use function parse_str;
use function parse_url;
use function rtrim;
use function strtr;

/**
 * Drives a real league authorization server through the whole issuance chain, on real
 * repositories over an in-memory record store, because the resource binding only exists
 * as the composition of parts that are each correct on their own.
 *
 * The per-unit tests around this one all passed while a refresh handed back a token that
 * opened nothing: the entity is right to omit `aud` when no resource is set
 * ({@see AccessTokenEntityTest}), the validator is right to refuse a token without one
 * ({@see \Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Token\EmbeddedTokenValidatorTest}),
 * and the grants recover the binding from a store that has the record
 * ({@see \Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Server\Grant\LoopbackAuthCodeGrantTest}).
 * What no unit could see is that a grant can reach the no-resource state at all: every one
 * of them stubs the store, and a stub that always has the record cannot lose it.
 *
 * So the store here is a real implementation with real state, and losing a row - a wiped
 * table, a restore from an older backup, the expired-record cleanup - is expressed as
 * removing one, which is the state no stub could hold.
 *
 * @internal
 */
final class ResourceBindingLifecycleTest extends Unit
{
    private const string CLIENT_ID = 'studio-mcp';

    private const string REDIRECT_URI = 'http://127.0.0.1:8080/callback';

    private const string RESOURCE = 'https://pimcore.example.com/pimcore-mcp';

    private const string SCOPE = 'mcp:read';

    private const string ISSUER = 'https://pimcore.example.com';

    private const string ENCRYPTION_KEY = 'def000004242424242424242424242424242424242424242424242424242424242';

    private InMemoryTokenRecordStore $store;

    private AuthorizationServer $server;

    private string $publicKey;

    public function _before(): void
    {
        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($resource, $privateKey);
        /** @var array{key: string} $details */
        $details = openssl_pkey_get_details($resource);
        $this->publicKey = $details['key'];

        $this->store = new InMemoryTokenRecordStore();
        $this->server = $this->authorizationServer((string) $privateKey);
    }

    /**
     * The binding has to reach the claim a resource server actually reads, and survive a
     * refresh. Both legs mint through the real response type, so this fails if the
     * audience is dropped anywhere between the authorization request and the JWT.
     */
    public function testTheAudienceSurvivesIssuanceAndRefresh(): void
    {
        $issued = $this->exchange($this->authorize());
        $this->assertSame([self::RESOURCE], $this->claims($issued['access_token'])->claims()->get('aud'));

        $refreshed = $this->token([
            'grant_type' => 'refresh_token',
            'client_id' => self::CLIENT_ID,
            'refresh_token' => $issued['refresh_token'],
        ]);

        $this->assertSame([self::RESOURCE], $this->claims($refreshed['access_token'])->claims()->get('aud'));
    }

    /**
     * A client that omits `scope` gets the resource's declared scopes on the token, not an
     * empty set. Driven end to end because the scopes travel from the authorization request
     * through the encrypted code to the JWT claim, and the claim is what an application reads.
     */
    public function testARequestWithoutScopeIsIssuedTheScopesTheResourceDeclares(): void
    {
        $issued = $this->exchange($this->authorize(null));

        $this->assertSame(self::SCOPE, $this->claims($issued['access_token'])->claims()->get('scope'));
        $this->assertSame(self::SCOPE, $issued['scope'] ?? null);
    }

    /**
     * league validates a refresh token from its own encrypted payload and reads an unknown
     * identifier as "not revoked", so nothing but this refuses a token whose record is gone.
     * Refreshing it anyway answers 200 with an audience-less token that every protected
     * resource rejects, which surfaces as an unexplained 401 somewhere else entirely.
     */
    public function testARefreshTokenIsRefusedOnceItsRecordIsGone(): void
    {
        $issued = $this->exchange($this->authorize());

        $this->store->forgetType(OAuthTokenRecord::TYPE_REFRESH);

        $this->expectRefusal(fn (): array => $this->token([
            'grant_type' => 'refresh_token',
            'client_id' => self::CLIENT_ID,
            'refresh_token' => $issued['refresh_token'],
        ]));
    }

    /**
     * The same hole on the authorization-code leg: the code carries its own encrypted
     * payload, so league accepts it after the record it was bound through is gone.
     */
    public function testAnAuthorizationCodeIsRefusedOnceItsRecordIsGone(): void
    {
        $code = $this->authorize();

        $this->store->forgetType(OAuthTokenRecord::TYPE_AUTH_CODE);

        $this->expectRefusal(fn (): array => $this->exchange($code));
        $this->assertSame(
            [],
            $this->store->identifiersOfType(OAuthTokenRecord::TYPE_ACCESS),
            'A refused exchange must not leave a persisted access token behind.',
        );
    }

    /**
     * @param callable(): array<string, mixed> $call
     */
    private function expectRefusal(callable $call): void
    {
        try {
            $call();
        } catch (OAuthServerException $exception) {
            $this->assertSame('invalid_grant', $exception->getErrorType());
            $this->assertStringContainsString('not bound to a protected resource', (string) $exception->getHint());

            return;
        }

        $this->fail('A grant with no recoverable resource binding was accepted.');
    }

    /**
     * Runs the authorization leg and returns the authorization code it redirects with.
     */
    private function authorize(?string $scope = self::SCOPE): string
    {
        $query = [
            'client_id' => self::CLIENT_ID,
            'response_type' => 'code',
            'redirect_uri' => self::REDIRECT_URI,
            'resource' => self::RESOURCE,
            'code_challenge' => $this->codeChallenge(),
            'code_challenge_method' => 'S256',
        ];
        if ($scope !== null) {
            $query['scope'] = $scope;
        }

        $request = (new ServerRequest('GET', self::ISSUER . '/pimcore-oauth/authorize'))->withQueryParams($query);

        $authorizationRequest = $this->server->validateAuthorizationRequest($request);
        $authorizationRequest->setUser(new UserEntity('21'));
        $authorizationRequest->setAuthorizationApproved(true);

        $location = $this->server->completeAuthorizationRequest($authorizationRequest, new Response())
            ->getHeaderLine('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertIsString($query['code'] ?? null, 'The authorization leg returned no code.');

        return (string) $query['code'];
    }

    /**
     * @return array<string, mixed>
     */
    private function exchange(string $code): array
    {
        return $this->token([
            'grant_type' => 'authorization_code',
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'code' => $code,
            'code_verifier' => $this->codeVerifier(),
        ]);
    }

    /**
     * @param array<string, string> $body
     *
     * @return array<string, mixed>
     */
    private function token(array $body): array
    {
        $response = $this->server->respondToAccessTokenRequest($this->tokenRequest($body), new Response());

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $response->getBody(), true);

        return $decoded;
    }

    /**
     * @param array<string, string> $body
     */
    private function tokenRequest(array $body): ServerRequestInterface
    {
        return (new ServerRequest('POST', self::ISSUER . '/pimcore-oauth/token'))->withParsedBody($body);
    }

    private function claims(mixed $jwt): UnencryptedToken
    {
        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->publicKey),
            InMemory::plainText($this->publicKey),
        );
        $token = $configuration->parser()->parse((string) $jwt);
        $this->assertInstanceOf(UnencryptedToken::class, $token);

        return $token;
    }

    private function authorizationServer(string $privateKey): AuthorizationServer
    {
        $resources = new ConfigProtectedResourceRegistry([
            ['uri' => self::RESOURCE, 'scopes_supported' => [self::SCOPE]],
        ]);

        $refreshTokenRepository = new RefreshTokenRepository($this->store);

        $server = new AuthorizationServer(
            $this->clientRepository(),
            new AccessTokenRepository(self::ISSUER, $this->store),
            new ScopeRepository(new ScopeRegistry($resources)),
            new CryptKey($privateKey, null, false),
            self::ENCRYPTION_KEY,
            new ScopedBearerTokenResponse(),
        );

        $authCodeGrant = new LoopbackAuthCodeGrant(
            new AuthCodeRepository($this->store),
            $refreshTokenRepository,
            new DateInterval('PT10M'),
            true,
            $resources,
            $this->store,
        );
        $authCodeGrant->setRefreshTokenTTL(new DateInterval('P1M'));
        $server->enableGrantType($authCodeGrant, new DateInterval('PT1H'));

        $refreshTokenGrant = new ResourceRefreshTokenGrant($refreshTokenRepository, $this->store);
        $refreshTokenGrant->setRefreshTokenTTL(new DateInterval('P1M'));
        $server->enableGrantType($refreshTokenGrant, new DateInterval('PT1H'));

        return $server;
    }

    private function clientRepository(): ClientRepositoryInterface
    {
        return $this->makeEmpty(ClientRepositoryInterface::class, [
            'getClientEntity' => new ClientEntity(self::CLIENT_ID, 'Studio MCP', self::REDIRECT_URI),
            'validateClient' => true,
        ]);
    }

    private function codeVerifier(): string
    {
        return 'verifier-0123456789-0123456789-0123456789-abc';
    }

    private function codeChallenge(): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $this->codeVerifier(), true)), '+/', '-_'), '=');
    }
}

/**
 * The record store as the database behaves, reduced to what the grants read back: rows
 * keyed by identifier, a duplicate refused, and rows that can go missing.
 *
 * @internal
 */
final class InMemoryTokenRecordStore implements TokenRecordStoreInterface
{
    /**
     * @var array<string, array{type: string, resource: string|null, revoked: bool}>
     */
    private array $records = [];

    public function persist(
        string $identifier,
        string $type,
        int $expiresAt,
        ?int $userId,
        ?string $clientId,
        ?string $resource = null
    ): void {
        if (isset($this->records[$identifier])) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $this->records[$identifier] = ['type' => $type, 'resource' => $resource, 'revoked' => false];
    }

    public function bindResource(string $identifier, ?string $resource): void
    {
        if (isset($this->records[$identifier])) {
            $this->records[$identifier]['resource'] = $resource;
        }
    }

    public function resourceFor(string $identifier): ?string
    {
        return $this->records[$identifier]['resource'] ?? null;
    }

    public function revoke(string $identifier): void
    {
        if (isset($this->records[$identifier])) {
            $this->records[$identifier]['revoked'] = true;
        }
    }

    public function isRevoked(string $identifier): bool
    {
        return $this->records[$identifier]['revoked'] ?? false;
    }

    public function deleteExpired(int $now): int
    {
        return 0;
    }

    /**
     * Drops every record of one kind, the way a wiped table or a restore from an older
     * backup leaves a client holding credentials this server has no record of.
     */
    public function forgetType(string $type): void
    {
        foreach ($this->identifiersOfType($type) as $identifier) {
            unset($this->records[$identifier]);
        }
    }

    /**
     * @return list<string>
     */
    public function identifiersOfType(string $type): array
    {
        $identifiers = [];
        foreach ($this->records as $identifier => $record) {
            if ($record['type'] === $type) {
                $identifiers[] = $identifier;
            }
        }

        return $identifiers;
    }
}
