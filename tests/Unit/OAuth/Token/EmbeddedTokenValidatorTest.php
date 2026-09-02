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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Token;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256 as HmacSha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\IdentityResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\TokenRevocationCheckerInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Token\EmbeddedTokenValidator;
use Pimcore\Model\User;
use Symfony\Component\Clock\MockClock;
use function array_key_exists;

final class EmbeddedTokenValidatorTest extends Unit
{
    private const string ISSUER = 'https://pimcore.example.com';

    private const string RESOURCE = 'https://pimcore.example.com/pimcore-mcp';

    /** @var array{private: string, public: string}|null */
    private static ?array $keyPair = null;

    /** @var array{private: string, public: string}|null */
    private static ?array $otherKeyPair = null;

    public function testValidTokenResolvesAccess(): void
    {
        $user = new User();
        $user->setUsername('agent-user');
        $keys = $this->keyPair();

        $access = $this->validator($keys['public'], $user)->validate($this->mint($keys), self::RESOURCE);

        $this->assertNotNull($access);
        $this->assertSame($user, $access->user);
        $this->assertSame(['mcp:read', 'mcp:write'], $access->scopes);
        $this->assertSame([self::RESOURCE], $access->audience);
        $this->assertSame('studio-mcp', $access->clientId);
    }

    /**
     * RFC 8707: a token minted for one protected resource must not be accepted at
     * another, which is what stops a token obtained for one application opening every
     * other one on the same authorization server.
     */
    public function testRejectsTokenMintedForAnotherResource(): void
    {
        $keys = $this->keyPair();
        $token = $this->mint($keys, ['aud' => 'https://example.com/pimcore-datahub-webservices/simplerest']);

        $this->assertNull($this->validator($keys['public'])->validate($token, self::RESOURCE));
    }

    /**
     * The audience is compared canonically, so a token whose `aud` differs only by a
     * trailing slash, host case or an explicit default port is still accepted.
     */
    public function testAcceptsAnEquivalentButNonCanonicalAudience(): void
    {
        $user = new User();
        $user->setUsername('agent-user');
        $keys = $this->keyPair();
        $token = $this->mint($keys, ['aud' => 'https://PIMCORE.EXAMPLE.com:443/pimcore-mcp/']);

        $this->assertNotNull($this->validator($keys['public'], $user)->validate($token, self::RESOURCE));
    }

    /**
     * A token that names no audience predates the binding or was issued for a request
     * that named no resource, and stays valid so existing clients keep working.
     */
    public function testAcceptsTokenWithoutAudience(): void
    {
        $user = new User();
        $user->setUsername('agent-user');
        $keys = $this->keyPair();
        $token = $this->mint($keys, ['aud' => null]);

        $this->assertNotNull($this->validator($keys['public'], $user)->validate($token, self::RESOURCE));
    }

    public function testRejectsExpiredToken(): void
    {
        $keys = $this->keyPair();
        $expired = $this->mint($keys, [
            'iat' => '2026-07-15T10:00:00+00:00',
            'exp' => '2026-07-15T11:00:00+00:00',
        ]);

        $this->assertNull($this->validator($keys['public'])->validate($expired, self::RESOURCE));
    }

    public function testRejectsWrongIssuer(): void
    {
        $keys = $this->keyPair();
        $token = $this->mint($keys, ['iss' => 'https://evil.example.com']);

        $this->assertNull($this->validator($keys['public'])->validate($token, self::RESOURCE));
    }

    public function testRejectsBadSignature(): void
    {
        // Minted with one key pair, verified against another's public key.
        $token = $this->mint($this->otherKeyPair());

        $this->assertNull($this->validator($this->keyPair()['public'])->validate($token, self::RESOURCE));
    }

    public function testRejectsRevokedToken(): void
    {
        $keys = $this->keyPair();

        $access = $this->validator($keys['public'], null, revoked: true)->validate($this->mint($keys), self::RESOURCE);

        $this->assertNull($access);
    }

    public function testRejectsUnresolvableUser(): void
    {
        $keys = $this->keyPair();

        $access = $this->validator($keys['public'], null)->validate($this->mint($keys), self::RESOURCE);

        $this->assertNull($access);
    }

    public function testRejectsGarbageToken(): void
    {
        $this->assertNull($this->validator($this->keyPair()['public'])->validate('not-a-jwt', self::RESOURCE));
    }

    public function testRejectsTokenWithoutExpiration(): void
    {
        $keys = $this->keyPair();
        $noExpiry = $this->mint($keys, ['exp' => null]);

        $this->assertNull($this->validator($keys['public'], new User())->validate($noExpiry, self::RESOURCE));
    }

    public function testRejectsAlgorithmConfusionHs256(): void
    {
        $keys = $this->keyPair();

        // Classic RS->HS confusion: the attacker HMAC-signs with the RSA public
        // key as the shared secret, hoping the RS verifies HS256 with that key.
        $config = Configuration::forSymmetricSigner(new HmacSha256(), InMemory::plainText($keys['public']));
        $forged = $config->builder()
            ->issuedBy(self::ISSUER)
            ->relatedTo('42')
            ->identifiedBy('jti-1')
            ->permittedFor(self::RESOURCE)
            ->issuedAt(new DateTimeImmutable('2026-07-15T12:00:00+00:00'))
            ->expiresAt(new DateTimeImmutable('2026-07-15T13:00:00+00:00'))
            ->withClaim('scope', 'mcp:read')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $this->assertNull($this->validator($keys['public'], new User())->validate($forged, self::RESOURCE));
    }

    public function testRejectsUnsecuredNoneToken(): void
    {
        $keys = $this->keyPair();

        // Hand-crafted alg=none token with an empty signature (the way an attacker
        // would forge one), rather than via the library which no longer mints them.
        $segment = static fn (array $data): string => rtrim(
            strtr(base64_encode(json_encode($data, JSON_THROW_ON_ERROR)), '+/', '-_'),
            '='
        );
        $forged = $segment(['alg' => 'none', 'typ' => 'JWT'])
            . '.' . $segment([
                'iss' => self::ISSUER,
                'sub' => '42',
                'jti' => 'jti-1',
                'aud' => self::RESOURCE,
                'iat' => 1_752_580_800,
                'exp' => 1_752_584_400,
                'scope' => 'mcp:read',
            ])
            . '.';

        $this->assertNull($this->validator($keys['public'], new User())->validate($forged, self::RESOURCE));
    }

    public function testReturnsNullWhenNoPublicKeyConfigured(): void
    {
        $validator = new EmbeddedTokenValidator(
            null,
            self::ISSUER,
            $this->makeEmpty(IdentityResolverInterface::class, ['resolve' => new User()]),
            $this->makeEmpty(TokenRevocationCheckerInterface::class, ['isRevoked' => false]),
            new MockClock(new DateTimeImmutable('2026-07-15T12:30:00+00:00')),
        );

        $this->assertNull($validator->validate($this->mint($this->keyPair()), self::RESOURCE));
    }

    private function validator(string $publicKey, ?User $user = null, bool $revoked = false): EmbeddedTokenValidator
    {
        return new EmbeddedTokenValidator(
            $publicKey,
            self::ISSUER,
            $this->makeEmpty(IdentityResolverInterface::class, ['resolve' => $user]),
            $this->makeEmpty(TokenRevocationCheckerInterface::class, ['isRevoked' => $revoked]),
            new MockClock(new DateTimeImmutable('2026-07-15T12:30:00+00:00')),
        );
    }

    /**
     * @param array{private: string, public: string} $keys
     * @param array<string, string|null> $overrides pass ['exp' => null] to omit the expiry claim
     */
    private function mint(array $keys, array $overrides = []): string
    {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($keys['private']),
            InMemory::plainText($keys['public']),
        );

        $builder = $config->builder()
            ->issuedBy($overrides['iss'] ?? self::ISSUER)
            ->relatedTo($overrides['sub'] ?? '42')
            ->identifiedBy($overrides['jti'] ?? 'jti-1')
            ->issuedAt(new DateTimeImmutable($overrides['iat'] ?? '2026-07-15T12:00:00+00:00'))
            ->withClaim('scope', $overrides['scope'] ?? 'mcp:read mcp:write')
            ->withClaim('client_id', $overrides['client_id'] ?? 'studio-mcp');

        // array_key_exists (not ??) so an explicit null omits the audience entirely,
        // which is how a token that names no resource is minted.
        if (!array_key_exists('aud', $overrides) || $overrides['aud'] !== null) {
            $builder = $builder->permittedFor($overrides['aud'] ?? self::RESOURCE);
        }

        // array_key_exists (not ??) so an explicit null omits the claim entirely.
        if (!array_key_exists('exp', $overrides) || $overrides['exp'] !== null) {
            $builder = $builder->expiresAt(new DateTimeImmutable($overrides['exp'] ?? '2026-07-15T13:00:00+00:00'));
        }

        return $builder->getToken($config->signer(), $config->signingKey())->toString();
    }

    /**
     * @return array{private: string, public: string}
     */
    private function keyPair(): array
    {
        return self::$keyPair ??= $this->generateKeyPair();
    }

    /**
     * @return array{private: string, public: string}
     */
    private function otherKeyPair(): array
    {
        return self::$otherKeyPair ??= $this->generateKeyPair();
    }

    /**
     * @return array{private: string, public: string}
     */
    private function generateKeyPair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($resource, $privateKey);

        return [
            'private' => $privateKey,
            'public' => openssl_pkey_get_details($resource)['key'],
        ];
    }
}
