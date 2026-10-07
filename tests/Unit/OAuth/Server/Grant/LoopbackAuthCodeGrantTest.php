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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Server\Grant;

use const PHP_URL_QUERY;
use Codeception\Test\Unit;
use DateInterval;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ConfigProtectedResourceRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ScopeRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\AuthCodeEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\UserEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Grant\LoopbackAuthCodeGrant;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ScopeRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\TokenRecordStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\RequestType\ResourceAuthorizationRequest;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionMethod;
use function array_map;
use function array_values;
use function parse_str;
use function parse_url;

final class LoopbackAuthCodeGrantTest extends Unit
{
    private const string CLIENT_ID = 'test-client';

    private const string REDIRECT_URI = 'http://127.0.0.1:8080/callback';

    // A valid RFC 7636 code_challenge (43–128 chars of the unreserved set).
    private const string CODE_CHALLENGE = 'abcdefghijklmnopqrstuvwxyz0123456789-._~ABCDE';

    private const string KNOWN_RESOURCE = 'https://example.com/pimcore-mcp';

    private const string ENCRYPTION_KEY = 'def000004242424242424242424242424242424242424242424242424242424242';

    /**
     * @param list<string>|null $supportedScopes null keeps the default single-scope resource
     */
    private function grant(
        bool $withResources = true,
        ?TokenRecordStoreInterface $store = null,
        string $defaultScope = '',
        ?array $supportedScopes = null,
    ): LoopbackAuthCodeGrant {
        $resources = $withResources ? [
            [
                'uri' => self::KNOWN_RESOURCE,
                'scopes_supported' => $supportedScopes ?? ['mcp:read'],
                'authorization_servers' => ['https://example.com/pimcore-oauth'],
            ],
        ] : [];

        $authCodeRepository = $this->createMock(AuthCodeRepositoryInterface::class);
        $authCodeRepository->method('getNewAuthCode')->willReturnCallback(
            static function (): AuthCodeEntity {
                $code = new AuthCodeEntity();
                $code->setIdentifier('auth-code-id');

                return $code;
            },
        );

        $grant = new LoopbackAuthCodeGrant(
            $authCodeRepository,
            $this->createMock(RefreshTokenRepositoryInterface::class),
            new DateInterval('PT10M'),
            true,
            new ConfigProtectedResourceRegistry($resources),
            $store ?? $this->createMock(TokenRecordStoreInterface::class),
        );

        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->method('getClientEntity')->willReturn(
            new ClientEntity(self::CLIENT_ID, 'Test client', self::REDIRECT_URI),
        );
        $grant->setClientRepository($clientRepository);
        // The catalogue is derived from the same resources the grant narrows against,
        // so a scope exists exactly when some resource supports it.
        $grant->setScopeRepository(new ScopeRepository($this->scopeRegistry()));
        $grant->setDefaultScope($defaultScope);

        return $grant;
    }

    private function scopeRegistry(): ScopeRegistry
    {
        return new ScopeRegistry(
            new ConfigProtectedResourceRegistry([
                [
                    'uri' => 'https://catalogue.example/pimcore-mcp',
                    'scopes_supported' => ['mcp:read', 'mcp:write'],
                ],
            ]),
        );
    }

    /**
     * @param array<string, string> $extra
     */
    private function authorizeRequest(array $extra): ServerRequestInterface
    {
        return (new ServerRequest('GET', '/pimcore-oauth/authorize'))->withQueryParams([
            'response_type' => 'code',
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'scope' => 'mcp:read',
            'state' => 'xyz',
            ...$extra,
        ]);
    }

    /**
     * OAuth 2.1 requires PKCE from every client. league only enforces a challenge for
     * public ones, so without this check a confidential client registered through DCR
     * could run the whole authorization-code flow unprotected. Its secret does not cover
     * the gap: PKCE protects the code, not the client.
     */
    public function testRequestWithoutCodeChallengeIsRejected(): void
    {
        $exception = $this->assertRejectedAsInvalidRequest([
            'resource' => self::KNOWN_RESOURCE,
        ]);

        $this->assertStringContainsString('code_challenge', $exception->getHint() ?? '');
    }

    /**
     * The refusal must not depend on the client being public. A confidential client is the
     * one league would have waved through, so it is the case worth pinning.
     */
    public function testConfidentialClientAlsoRequiresACodeChallenge(): void
    {
        $grant = $this->grant();
        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->method('getClientEntity')->willReturn(
            new ClientEntity(self::CLIENT_ID, 'Confidential client', self::REDIRECT_URI, true),
        );
        $grant->setClientRepository($clientRepository);

        try {
            $grant->validateAuthorizationRequest($this->authorizeRequest([
                'resource' => self::KNOWN_RESOURCE,
            ]));
            $this->fail('Expected the request to be rejected.');
        } catch (OAuthServerException $exception) {
            $this->assertSame('invalid_request', $exception->getErrorType());
            $this->assertStringContainsString('code_challenge', $exception->getHint() ?? '');
        }
    }

    public function testPlainCodeChallengeMethodIsRejected(): void
    {
        $this->assertRejectedAsInvalidRequest([
            'code_challenge' => self::CODE_CHALLENGE,
            'code_challenge_method' => 'plain',
        ]);
    }

    public function testOmittedCodeChallengeMethodDefaultsToPlainAndIsRejected(): void
    {
        // No code_challenge_method → league defaults to plain → must be rejected.
        $this->assertRejectedAsInvalidRequest([
            'code_challenge' => self::CODE_CHALLENGE,
        ]);
    }

    /**
     * @param array<string, string> $extra
     */
    private function assertRejectedAsInvalidRequest(
        array $extra,
        bool $withResources = true,
    ): OAuthServerException {
        try {
            $this->grant($withResources)->validateAuthorizationRequest($this->authorizeRequest($extra));
            $this->fail('Expected the request to be rejected.');
        } catch (OAuthServerException $exception) {
            $this->assertSame('invalid_request', $exception->getErrorType());

            return $exception;
        }
    }

    public function testS256CodeChallengeIsAccepted(): void
    {
        $authRequest = $this->grant()->validateAuthorizationRequest($this->authorizeRequest([
            'code_challenge' => self::CODE_CHALLENGE,
            'code_challenge_method' => 'S256',
            'resource' => self::KNOWN_RESOURCE,
        ]));

        $this->assertSame(self::CLIENT_ID, $authRequest->getClient()->getIdentifier());
        $this->assertSame('S256', $authRequest->getCodeChallengeMethod());
    }

    public function testUnknownResourceIsRejected(): void
    {
        // RFC 8707: an unregistered audience must be refused, not silently ignored.
        $exception = $this->assertRejectedAsInvalidRequest([
            'code_challenge' => self::CODE_CHALLENGE,
            'code_challenge_method' => 'S256',
            'resource' => 'https://elsewhere.example/mcp',
        ]);

        $this->assertStringContainsString('resource', (string) $exception->getHint());
    }

    /**
     * The other half of the spoofed-host regression. The registry is seeded from the
     * configured issuer only, so a resource named after an attacker-supplied `Host` is
     * not in it, and naming it here is refused rather than minting a token whose
     * audience the attacker controls.
     */
    public function testResourceNamingAForeignHostIsRejected(): void
    {
        $exception = $this->assertRejectedAsInvalidRequest([
            'code_challenge' => self::CODE_CHALLENGE,
            'code_challenge_method' => 'S256',
            'resource' => 'https://evil.example/pimcore-mcp',
        ]);

        $this->assertStringContainsString('not a known protected resource', $exception->getHint() ?? '');
    }

    public function testKnownResourceIsAcceptedAndCarriedOnTheRequest(): void
    {
        $authRequest = $this->grant()->validateAuthorizationRequest($this->authorizeRequest([
            'code_challenge' => self::CODE_CHALLENGE,
            'code_challenge_method' => 'S256',
            'resource' => self::KNOWN_RESOURCE,
        ]));

        $this->assertInstanceOf(ResourceAuthorizationRequest::class, $authRequest);
        $this->assertSame(self::KNOWN_RESOURCE, $authRequest->getResource());
    }

    public function testResourceLookupIsCanonicalised(): void
    {
        // A trailing-slash / differently-cased variant of a registered resource
        // is the same audience and must be accepted.
        $authRequest = $this->grant()->validateAuthorizationRequest($this->authorizeRequest([
            'code_challenge' => self::CODE_CHALLENGE,
            'code_challenge_method' => 'S256',
            'resource' => 'https://EXAMPLE.com/pimcore-mcp/',
        ]));

        $this->assertInstanceOf(ResourceAuthorizationRequest::class, $authRequest);
    }

    /**
     * The resource is required, not optional. A token naming none is refused everywhere,
     * so accepting the request would only mint a credential that opens nothing; refusing
     * it surfaces the client's mistake at authorization time instead.
     */
    public function testRequestWithoutResourceIsRejected(): void
    {
        $exception = $this->assertRejectedAsInvalidRequest([
            'code_challenge' => self::CODE_CHALLENGE,
            'code_challenge_method' => 'S256',
        ]);

        $this->assertStringContainsString('resource', $exception->getHint() ?? '');
    }

    /**
     * The binding has to survive the round trip through league's encrypted authorization
     * code: it is written when the code is issued, and recovered by decrypting the code
     * at the token request. Both ends key off `auth_code_id`, which is league's name and
     * not ours, so this drives the real encoder rather than a hand-built payload.
     */
    public function testResourceSurvivesTheAuthorizationCodeRoundTrip(): void
    {
        $bound = [];
        $store = $this->makeEmpty(TokenRecordStoreInterface::class, [
            'bindResource' => function (string $identifier, ?string $resource) use (&$bound): void {
                $bound[$identifier] = $resource;
            },
            'resourceFor' => function (string $identifier) use (&$bound): ?string {
                return $bound[$identifier] ?? null;
            },
        ]);

        $grant = $this->grant(store: $store);
        $grant->setEncryptionKey(self::ENCRYPTION_KEY);

        $authRequest = $grant->validateAuthorizationRequest($this->authorizeRequest([
            'code_challenge' => self::CODE_CHALLENGE,
            'code_challenge_method' => 'S256',
            'resource' => self::KNOWN_RESOURCE,
        ]));
        $authRequest->setUser(new UserEntity('21'));
        $authRequest->setAuthorizationApproved(true);

        $redirect = $grant->completeAuthorizationRequest($authRequest)
            ->generateHttpResponse(new Response())
            ->getHeaderLine('Location');
        parse_str((string) parse_url($redirect, PHP_URL_QUERY), $query);

        // The code was bound as it was issued.
        $this->assertSame([self::KNOWN_RESOURCE], array_values($bound));

        // And decrypting it at the token request recovers that binding.
        $recovered = (new ReflectionMethod($grant, 'boundResource'))->invoke(
            $grant,
            (new ServerRequest('POST', 'https://example.com/pimcore-oauth/token'))
                ->withParsedBody(['code' => $query['code'] ?? '']),
        );

        $this->assertSame(self::KNOWN_RESOURCE, $recovered);
    }

    /**
     * The mirror of the round trip above: when the binding cannot be recovered - the record
     * store was wiped, restored from an older backup, or the expired-record cleanup removed
     * the row - the exchange must be refused instead of minting an unbound token. league
     * validates the code from its own encrypted payload and never consults the record, so
     * without this the client is handed HTTP 200 and a credential the validator refuses at
     * every protected resource, and the lost binding only surfaces as a 401 somewhere else.
     *
     * The access-token repository is asserted untouched: the refusal has to come before the
     * token is minted and persisted, not after.
     */
    public function testAnUnboundAuthorizationCodeIsRefusedWithoutMintingAToken(): void
    {
        $accessTokenRepository = $this->createMock(AccessTokenRepositoryInterface::class);
        $accessTokenRepository->expects($this->never())->method('getNewToken');
        $accessTokenRepository->expects($this->never())->method('persistNewAccessToken');

        // resourceFor() answers null for every identifier: the record is gone.
        $grant = $this->grant(store: $this->makeEmpty(TokenRecordStoreInterface::class));
        $grant->setAccessTokenRepository($accessTokenRepository);

        $this->expectException(OAuthServerException::class);

        try {
            (new ReflectionMethod($grant, 'issueAccessToken'))->invoke(
                $grant,
                new DateInterval('PT1H'),
                new ClientEntity(self::CLIENT_ID, 'Test client', self::REDIRECT_URI),
                '21',
                [],
            );
        } catch (OAuthServerException $exception) {
            $this->assertSame('invalid_grant', $exception->getErrorType());
            $this->assertStringContainsString('not bound to a protected resource', (string) $exception->getHint());

            throw $exception;
        }
    }

    /**
     * `state` is optional for every client, and league returns null for an absent one
     * while its setter takes a non-nullable string. Copying it through unguarded turned
     * a spec-legal request into a TypeError, which no OAuthServerException handler
     * catches, so the authorization endpoint answered 500 before consent was reached.
     */
    public function testAuthorizationRequestWithoutStateIsAccepted(): void
    {
        $authRequest = $this->grant()->validateAuthorizationRequest($this->authorizeRequest([
            'code_challenge' => self::CODE_CHALLENGE,
            'code_challenge_method' => 'S256',
            'resource' => self::KNOWN_RESOURCE,
            'state' => null,
        ]));

        $this->assertInstanceOf(ResourceAuthorizationRequest::class, $authRequest);
        $this->assertNull($authRequest->getState());
        $this->assertSame(self::KNOWN_RESOURCE, $authRequest->getResource());
    }

    /**
     * RFC 8707: the token is downscoped to what the named resource can process. Narrowing
     * at authorization time is what keeps the consent screen honest, since the screen
     * renders the scopes carried on this request.
     */
    public function testScopesAreNarrowedToWhatTheResourceSupports(): void
    {
        $authRequest = $this->grant(defaultScope: '', supportedScopes: ['mcp:read'])
            ->validateAuthorizationRequest($this->authorizeRequest([
                'code_challenge' => self::CODE_CHALLENGE,
                'code_challenge_method' => 'S256',
                'resource' => self::KNOWN_RESOURCE,
                'scope' => 'mcp:read mcp:write',
            ]));

        $this->assertSame(['mcp:read'], $this->scopeIdentifiers($authRequest));
    }

    /**
     * The production configuration: no default scope is ever set, so a client that names
     * none arrives with an empty list. Narrowing must leave it alone rather than read it
     * as "asked for nothing this resource supports" and refuse a working client.
     */
    public function testARequestNamingNoScopeIsAccepted(): void
    {
        $authRequest = $this->grant(defaultScope: '', supportedScopes: ['mcp:read'])
            ->validateAuthorizationRequest($this->authorizeRequest([
                'code_challenge' => self::CODE_CHALLENGE,
                'code_challenge_method' => 'S256',
                'resource' => self::KNOWN_RESOURCE,
                'scope' => null,
            ]));

        $this->assertSame([], $this->scopeIdentifiers($authRequest));
    }

    /**
     * A resource may declare no scopes at all, and since `scopes_supported` now defaults
     * to `[]` that is what an entry with no explicit scopes gets. It constrains nothing,
     * so it must not brick every request naming it.
     */
    public function testAResourceDeclaringNoScopesConstrainsNothing(): void
    {
        $authRequest = $this->grant(defaultScope: '', supportedScopes: [])
            ->validateAuthorizationRequest($this->authorizeRequest([
                'code_challenge' => self::CODE_CHALLENGE,
                'code_challenge_method' => 'S256',
                'resource' => self::KNOWN_RESOURCE,
                'scope' => 'mcp:read mcp:write',
            ]));

        $this->assertSame(['mcp:read', 'mcp:write'], $this->scopeIdentifiers($authRequest));
    }

    /**
     * Asking only for scopes the resource cannot process would yield a token that opens
     * nothing. league raises its own `invalid_scope` as a redirect, and so must this one,
     * or the client gets a response it has no way to interpret.
     */
    public function testAskingOnlyForUnsupportedScopesIsRefusedAsARedirect(): void
    {
        try {
            $this->grant(defaultScope: '', supportedScopes: ['mcp:read'])
                ->validateAuthorizationRequest($this->authorizeRequest([
                    'code_challenge' => self::CODE_CHALLENGE,
                    'code_challenge_method' => 'S256',
                    'resource' => self::KNOWN_RESOURCE,
                    'scope' => 'mcp:write',
                ]));
            $this->fail('Expected the request to be rejected.');
        } catch (OAuthServerException $exception) {
            $this->assertSame('invalid_scope', $exception->getErrorType());
            $this->assertNotNull($exception->getRedirectUri());
        }
    }

    /**
     * @return list<string>
     */
    private function scopeIdentifiers(AuthorizationRequestInterface $request): array
    {
        return array_map(
            static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(),
            $request->getScopes(),
        );
    }

    /**
     * There is no discovery document listing an authorization server's resources, so a
     * client that guessed wrong has nowhere to look. The refusal names them instead.
     */
    public function testRefusalNamesTheKnownResources(): void
    {
        $exception = $this->assertRejectedAsInvalidRequest([
            'code_challenge' => self::CODE_CHALLENGE,
            'code_challenge_method' => 'S256',
            'resource' => 'https://example.com/not-a-resource',
        ]);

        $this->assertStringContainsString(self::KNOWN_RESOURCE, $exception->getHint() ?? '');
    }

    /**
     * An empty registry and a missing parameter produce the same symptom, so they have
     * to read differently: nothing can be issued at all until a resource is declared.
     */
    public function testRefusalSaysWhenNoResourceIsConfiguredAtAll(): void
    {
        $exception = $this->assertRejectedAsInvalidRequest(
            [
                'code_challenge' => self::CODE_CHALLENGE,
                'code_challenge_method' => 'S256',
            ],
            withResources: false,
        );

        $this->assertStringContainsString('no protected resources configured', $exception->getHint() ?? '');
    }
}
