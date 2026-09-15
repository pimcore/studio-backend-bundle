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
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Nyholm\Psr7\ServerRequest;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ClientMetadataResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Grant\ResourceRefreshTokenGrant;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ClientRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\DynamicClientStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\TokenRecordStoreInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionMethod;
use function json_encode;
use function time;

/**
 * @internal
 */
final class ResourceRefreshTokenGrantTest extends Unit
{
    private const string CLIENT_ID = 'studio-mcp';

    private const string REFRESH_TOKEN_ID = 'refresh-token-id';

    private const string RESOURCE = 'https://pimcore.example.com/pimcore-mcp/studio/product-read';

    /**
     * The binding survives a refresh only because the record is looked up by the id
     * league puts in its decrypted payload. That key is league's, not ours, so this
     * drives the real parent implementation rather than a stub: if the key is renamed
     * on an upgrade, the lookup silently misses and every refreshed token comes back
     * unbound, which the validator then refuses everywhere.
     */
    public function testRecoversTheBindingFromLeaguesDecryptedPayload(): void
    {
        $seen = [];
        $store = $this->makeEmpty(TokenRecordStoreInterface::class, [
            'resourceFor' => function (string $tokenId) use (&$seen): ?string {
                $seen[] = $tokenId;

                return $tokenId === self::REFRESH_TOKEN_ID ? self::RESOURCE : null;
            },
        ]);

        $data = $this->validateOldRefreshToken($this->grant($store), $this->refreshRequest());

        $this->assertSame([self::REFRESH_TOKEN_ID], $seen);
        $this->assertSame(self::REFRESH_TOKEN_ID, $data['refresh_token_id']);
    }

    /**
     * The refusal happens inside league's own path: AbstractGrant::getClientEntityOrFail()
     * asks the entity whether it supports the grant being used, and raises
     * `unauthorized_client` when it does not. Driven through the real ClientRepository so
     * this covers the whole chain from the persisted registration to the OAuth error.
     */
    public function testClientRegisteredWithoutRefreshIsRefusedAtTheTokenEndpoint(): void
    {
        $grant = $this->grantForClient(['authorization_code']);

        try {
            $this->getClientEntityOrFail($grant, 'dcr_client');
            $this->fail('A client that did not register refresh_token must be refused.');
        } catch (OAuthServerException $exception) {
            $this->assertSame('unauthorized_client', $exception->getErrorType());
            // A client error, not a 500: league answers with the standard OAuth envelope.
            $this->assertSame(400, $exception->getHttpStatusCode());
        }
    }

    public function testClientRegisteredWithRefreshIsAccepted(): void
    {
        $grant = $this->grantForClient(['authorization_code', 'refresh_token']);

        $client = $this->getClientEntityOrFail($grant, 'dcr_client');

        $this->assertSame('dcr_client', $client->getIdentifier());
    }

    /**
     * A client an operator declared in configuration carries no grant_types, so it must
     * stay unrestricted rather than losing refresh_token to an inferred restriction.
     */
    public function testPreRegisteredClientIsNotRefused(): void
    {
        $grant = $this->grant($this->makeEmpty(TokenRecordStoreInterface::class));
        $grant->setClientRepository(new ClientRepository(
            ['swagger-ui' => ['name' => 'Swagger UI', 'redirect_uris' => ['https://app/cb']]],
            $this->makeEmpty(ClientMetadataResolverInterface::class, ['resolve' => null]),
            $this->makeEmpty(DynamicClientStoreInterface::class, ['find' => null]),
        ));

        $client = $this->getClientEntityOrFail($grant, 'swagger-ui');

        $this->assertSame('swagger-ui', $client->getIdentifier());
    }

    /**
     * @param list<string> $grantTypes
     */
    private function grantForClient(array $grantTypes): ResourceRefreshTokenGrant
    {
        $dynamic = new DynamicClient('dcr_client', 'Dyn', ['https://app/cb'], $grantTypes, [], false, null);

        $grant = $this->grant($this->makeEmpty(TokenRecordStoreInterface::class));
        $grant->setClientRepository(new ClientRepository(
            [],
            $this->makeEmpty(ClientMetadataResolverInterface::class, ['resolve' => null]),
            $this->makeEmpty(DynamicClientStoreInterface::class, [
                'find' => fn (string $id): ?DynamicClient => $id === 'dcr_client' ? $dynamic : null,
            ]),
        ));

        return $grant;
    }

    /**
     * @throws OAuthServerException
     */
    private function getClientEntityOrFail(
        ResourceRefreshTokenGrant $grant,
        string $clientId,
    ): ClientEntityInterface {
        $method = new ReflectionMethod($grant, 'getClientEntityOrFail');

        return $method->invoke($grant, $clientId, new ServerRequest('POST', '/pimcore-oauth/token'));
    }

    private function grant(TokenRecordStoreInterface $store): ResourceRefreshTokenGrant
    {
        $repository = $this->makeEmpty(RefreshTokenRepositoryInterface::class, [
            'isRefreshTokenRevoked' => false,
        ]);

        $grant = new ResourceRefreshTokenGrant($repository, $store);
        $grant->setEncryptionKey(self::encryptionKey());

        return $grant;
    }

    /**
     * The grant is final, and rightly so: reach the protected seam by reflection rather
     * than opening the class up for a test.
     *
     * @return array<string, mixed>
     */
    private function validateOldRefreshToken(
        ResourceRefreshTokenGrant $grant,
        ServerRequestInterface $request,
    ): array {
        /** @var array<string, mixed> $data */
        $data = (new ReflectionMethod($grant, 'validateOldRefreshToken'))
            ->invoke($grant, $request, self::CLIENT_ID);

        return $data;
    }

    private function refreshRequest(): ServerRequestInterface
    {
        // The payload league itself writes when it issues a refresh token.
        $payload = json_encode([
            'client_id' => self::CLIENT_ID,
            'refresh_token_id' => self::REFRESH_TOKEN_ID,
            'access_token_id' => 'access-token-id',
            'scopes' => ['mcp:read'],
            'user_id' => '21',
            'expire_time' => time() + 3600,
        ]);

        $encrypted = Crypto::encryptWithPassword((string) $payload, self::encryptionKey());

        return (new ServerRequest('POST', 'https://pimcore.example.com/pimcore-oauth/token'))
            ->withParsedBody(['refresh_token' => $encrypted, 'client_id' => self::CLIENT_ID]);
    }

    private static function encryptionKey(): string
    {
        return 'def000004242424242424242424242424242424242424242424242424242424242';
    }
}
