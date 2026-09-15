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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DependencyInjection;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * The OAuth server cannot run without an issuer or key material, and both failures
 * surface far from their cause: a missing issuer leaves tokens unsigned by any `iss`
 * while metadata advertises one, and missing keys make AuthorizationServerFactory throw
 * a plain RuntimeException that AuthorizeController does not catch, so it escapes as a
 * 500 on a public unauthenticated path. Catching both at container build turns them into
 * configuration errors that name the key.
 *
 * @internal
 */
final class OAuthConfigurationTest extends Unit
{
    private const string ISSUER = 'https://pimcore.example.com';

    private const array KEYS = [
        'private_key' => '/keys/private.key',
        'public_key' => '/keys/public.key',
        'encryption_key' => 'enc',
    ];

    public function testDisabledNeedsNeitherIssuerNorKeys(): void
    {
        $config = $this->process(['enabled' => false]);

        $this->assertFalse($config['enabled']);
        $this->assertNull($config['issuer']);
    }

    /**
     * The default configuration, i.e. no `oauth` key at all, must stay valid: the feature
     * is opt-in and every installation processes this tree.
     */
    public function testAbsentOAuthSectionIsValid(): void
    {
        $processed = (new Processor())->processConfiguration(new Configuration(), [[]]);

        $this->assertFalse($processed['oauth']['enabled']);
    }

    public function testEnabledWithIssuerAndAllKeysIsAccepted(): void
    {
        $config = $this->process(['enabled' => true, 'issuer' => self::ISSUER, 'keys' => self::KEYS]);

        $this->assertSame(self::ISSUER, $config['issuer']);
        $this->assertSame('/keys/private.key', $config['keys']['private_key']);
    }

    /**
     * Everything downstream concatenates a root path onto the issuer and compares the result
     * byte for byte, so a value that is merely present is not enough: a trailing slash or a
     * path yields `https://host//pimcore-mcp`, and an uppercase host yields an audience the
     * resource server never matches. Both compile happily without this.
     *
     * @dataProvider malformedIssuerProvider
     */
    public function testEnabledWithAMalformedIssuerIsRejected(string $issuer): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/must be a bare origin/');

        $this->process(['enabled' => true, 'issuer' => $issuer, 'keys' => self::KEYS]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function malformedIssuerProvider(): array
    {
        return [
            'empty' => [''],
            'trailing slash' => ['https://pimcore.example.com/'],
            'with a path' => ['https://pimcore.example.com/oauth'],
            'with a query' => ['https://pimcore.example.com?a=b'],
            'with a fragment' => ['https://pimcore.example.com#x'],
            'uppercase host' => ['https://PIMCORE.example.com'],
            'redundant default port' => ['https://pimcore.example.com:443'],
            'not absolute' => ['pimcore.example.com'],
            'wrong scheme' => ['ftp://pimcore.example.com'],
            'surrounding whitespace' => [' https://pimcore.example.com'],
            'with credentials' => ['https://user:pw@pimcore.example.com'],
        ];
    }

    /**
     * @dataProvider validIssuerProvider
     */
    public function testEnabledWithACanonicalOriginIsAccepted(string $issuer): void
    {
        $config = $this->process(['enabled' => true, 'issuer' => $issuer, 'keys' => self::KEYS]);

        $this->assertSame($issuer, $config['issuer']);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function validIssuerProvider(): array
    {
        return [
            'https origin' => ['https://pimcore.example.com'],
            'http for local development' => ['http://localhost'],
            'explicit non-default port' => ['http://localhost:8080'],
        ];
    }

    /**
     * A malformed issuer only matters once the server is running, so it must not break the
     * build of an installation that never enabled OAuth.
     */
    public function testAMalformedIssuerIsIgnoredWhileDisabled(): void
    {
        $config = $this->process(['enabled' => false, 'issuer' => 'https://pimcore.example.com/']);

        $this->assertSame('https://pimcore.example.com/', $config['issuer']);
    }

    public function testEnabledWithoutIssuerIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/issuer must be set/');

        $this->process(['enabled' => true, 'keys' => self::KEYS]);
    }

    /**
     * @dataProvider missingKeyProvider
     */
    public function testEnabledWithAMissingKeyIsRejected(string $missing): void
    {
        $keys = self::KEYS;
        unset($keys[$missing]);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/must all be set/');

        $this->process(['enabled' => true, 'issuer' => self::ISSUER, 'keys' => $keys]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function missingKeyProvider(): array
    {
        return [
            'private key' => ['private_key'],
            'public key' => ['public_key'],
            'encryption key' => ['encryption_key'],
        ];
    }

    public function testEnabledWithNoKeysAtAllIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/must all be set/');

        $this->process(['enabled' => true, 'issuer' => self::ISSUER]);
    }

    /**
     * A signing key without a passphrase is the normal case, so requiring the other three
     * must not drag this one in with them.
     */
    public function testPassphraseStaysOptional(): void
    {
        $config = $this->process(['enabled' => true, 'issuer' => self::ISSUER, 'keys' => self::KEYS]);

        $this->assertNull($config['keys']['passphrase']);
    }

    /**
     * Dynamic client registration is a sub-flag of a feature that is off, so it stays
     * merely inert rather than becoming a hard configuration error. The endpoint is made
     * unreachable in the wiring instead, which is what keeps a partial documentation
     * snippet like `dynamic_client_registration: {enabled: true}` a valid thing to write.
     */
    public function testDynamicClientRegistrationWhileDisabledIsTolerated(): void
    {
        $config = $this->process(['enabled' => false, 'dynamic_client_registration' => ['enabled' => true]]);

        $this->assertFalse($config['enabled']);
        $this->assertTrue($config['dynamic_client_registration']['enabled']);
    }

    /**
     * @param array<string, mixed> $oauth
     *
     * @return array<string, mixed>
     */
    private function process(array $oauth): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [['oauth' => $oauth]])['oauth'];
    }
}
