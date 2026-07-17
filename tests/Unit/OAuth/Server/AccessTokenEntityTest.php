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

use Codeception\Test\Unit;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Plain;
use League\OAuth2\Server\CryptKey;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\AccessTokenEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ScopeEntity;

final class AccessTokenEntityTest extends Unit
{
    public function testEmitsRfc9068Claims(): void
    {
        [$private, $public] = $this->keyPair();

        $token = new AccessTokenEntity();
        $token->setClient(new ClientEntity('studio-mcp', 'Studio MCP', [], true, 21));
        $token->setIdentifier('jti-123');
        $token->setExpiryDateTime(new DateTimeImmutable('+1 hour'));
        $token->setUserIdentifier('21');
        $token->addScope(new ScopeEntity('mcp:read'));
        $token->addScope(new ScopeEntity('mcp:write'));
        $token->setIssuer('https://pimcore.example.com');
        $token->setPrivateKey(new CryptKey($private, null, false));

        $parsed = $this->parse($token->toString(), $public);
        $claims = $parsed->claims();

        // RFC 9068 shape: space-delimited scope string + client_id, subject is the user.
        $this->assertSame('21', $claims->get('sub'));
        $this->assertSame('mcp:read mcp:write', $claims->get('scope'));
        $this->assertSame('studio-mcp', $claims->get('client_id'));
        $this->assertSame('https://pimcore.example.com', $claims->get('iss'));
        $this->assertSame('jti-123', $claims->get('jti'));
        $this->assertTrue($claims->has('exp'));
        // The default league claims we deliberately replaced must be absent.
        $this->assertNull($claims->get('scopes'));
    }

    public function testSubjectFallsBackToClientWhenNoUser(): void
    {
        [$private, $public] = $this->keyPair();

        $token = new AccessTokenEntity();
        $token->setClient(new ClientEntity('studio-mcp', 'Studio MCP', [], true, null));
        $token->setIdentifier('jti-1');
        $token->setExpiryDateTime(new DateTimeImmutable('+1 hour'));
        $token->addScope(new ScopeEntity('mcp:read'));
        $token->setPrivateKey(new CryptKey($private, null, false));

        $claims = $this->parse($token->toString(), $public)->claims();
        $this->assertSame('studio-mcp', $claims->get('sub'));
    }

    private function parse(string $jwt, string $publicKey): Plain
    {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($publicKey),
            InMemory::plainText($publicKey),
        );
        $token = $config->parser()->parse($jwt);
        self::assertInstanceOf(Plain::class, $token);

        return $token;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function keyPair(): array
    {
        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($resource, $private);

        return [$private, openssl_pkey_get_details($resource)['key']];
    }
}
