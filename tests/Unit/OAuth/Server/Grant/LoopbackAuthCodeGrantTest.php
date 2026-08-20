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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Grant\LoopbackAuthCodeGrant;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ScopeRepository;
use Psr\Http\Message\ServerRequestInterface;

final class LoopbackAuthCodeGrantTest extends Unit
{
    private const string CLIENT_ID = 'test-client';
    private const string REDIRECT_URI = 'http://127.0.0.1:8080/callback';
    // A valid RFC 7636 code_challenge (43–128 chars of the unreserved set).
    private const string CODE_CHALLENGE = 'abcdefghijklmnopqrstuvwxyz0123456789-._~ABCDE';

    private function grant(): LoopbackAuthCodeGrant
    {
        $grant = new LoopbackAuthCodeGrant(
            $this->createMock(AuthCodeRepositoryInterface::class),
            $this->createMock(RefreshTokenRepositoryInterface::class),
            new DateInterval('PT10M'),
            true,
        );

        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->method('getClientEntity')->willReturn(
            new ClientEntity(self::CLIENT_ID, 'Test client', self::REDIRECT_URI),
        );
        $grant->setClientRepository($clientRepository);
        $grant->setScopeRepository(new ScopeRepository());
        $grant->setDefaultScope('mcp:read');

        return $grant;
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
    private function assertRejectedAsInvalidRequest(array $extra): void
    {
        try {
            $this->grant()->validateAuthorizationRequest($this->authorizeRequest($extra));
            $this->fail('Expected the request to be rejected.');
        } catch (OAuthServerException $exception) {
            $this->assertSame('invalid_request', $exception->getErrorType());
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
}
