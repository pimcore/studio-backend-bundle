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

use Codeception\Test\Unit;
use DateInterval;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeProviderInterface;
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
use function array_values;
use function parse_str;
use function parse_url;
use const PHP_URL_QUERY;

final class LoopbackAuthCodeGrantTest extends Unit
{
    private const string CLIENT_ID = 'test-client';

    private const string REDIRECT_URI = 'http://127.0.0.1:8080/callback';

    // A valid RFC 7636 code_challenge (43–128 chars of the unreserved set).
    private const string CODE_CHALLENGE = 'abcdefghijklmnopqrstuvwxyz0123456789-._~ABCDE';

    private const string KNOWN_RESOURCE = 'https://example.com/pimcore-mcp';

    private const string ENCRYPTION_KEY = 'def000004242424242424242424242424242424242424242424242424242424242';

    private function grant(
        bool $withResources = true,
        ?TokenRecordStoreInterface $store = null,
    ): LoopbackAuthCodeGrant {
        $resources = $withResources ? [
            [
                'uri' => self::KNOWN_RESOURCE,
                'scopes_supported' => ['mcp:read'],
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
        $grant->setScopeRepository(new ScopeRepository($this->scopeRegistry()));
        $grant->setDefaultScope('mcp:read');

        return $grant;
    }

    private function scopeRegistry(): ScopeRegistry
    {
        return new ScopeRegistry([
            new class implements ScopeProviderInterface {
                public function scopes(): array
                {
                    return ['mcp:read', 'mcp:write'];
                }
            },
        ]);
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
