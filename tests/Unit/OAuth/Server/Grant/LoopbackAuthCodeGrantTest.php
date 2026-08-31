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
use Nyholm\Psr7\ServerRequest;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ConfigProtectedResourceRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ScopeRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Grant\LoopbackAuthCodeGrant;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ScopeRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\RequestType\ResourceAuthorizationRequest;
use Psr\Http\Message\ServerRequestInterface;

final class LoopbackAuthCodeGrantTest extends Unit
{
    private const string CLIENT_ID = 'test-client';

    private const string REDIRECT_URI = 'http://127.0.0.1:8080/callback';

    // A valid RFC 7636 code_challenge (43–128 chars of the unreserved set).
    private const string CODE_CHALLENGE = 'abcdefghijklmnopqrstuvwxyz0123456789-._~ABCDE';

    private const string KNOWN_RESOURCE = 'https://example.com/pimcore-mcp';

    private function grant(): LoopbackAuthCodeGrant
    {
        $grant = new LoopbackAuthCodeGrant(
            $this->createMock(AuthCodeRepositoryInterface::class),
            $this->createMock(RefreshTokenRepositoryInterface::class),
            new DateInterval('PT10M'),
            true,
            new ConfigProtectedResourceRegistry([
                [
                    'uri' => self::KNOWN_RESOURCE,
                    'scopes_supported' => ['mcp:read'],
                    'authorization_servers' => ['https://example.com/pimcore-oauth'],
                ],
            ]),
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
    private function assertRejectedAsInvalidRequest(array $extra): OAuthServerException
    {
        try {
            $this->grant()->validateAuthorizationRequest($this->authorizeRequest($extra));
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

    public function testRequestWithoutResourceCarriesNoAudience(): void
    {
        $authRequest = $this->grant()->validateAuthorizationRequest($this->authorizeRequest([
            'code_challenge' => self::CODE_CHALLENGE,
            'code_challenge_method' => 'S256',
        ]));

        $this->assertInstanceOf(ResourceAuthorizationRequest::class, $authRequest);
        $this->assertNull($authRequest->getResource());
    }
}
