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
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Nyholm\Psr7\ServerRequest;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Grant\ResourceRefreshTokenGrant;
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
