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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Util;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;

final class CanonicalUriTest extends Unit
{
    public function testLowercasesSchemeAndHost(): void
    {
        $this->assertSame(
            'https://pimcore.example.com/pimcore-mcp',
            CanonicalUri::canonicalize('HTTPS://Pimcore.Example.COM/pimcore-mcp')
        );
    }

    public function testStripsTrailingSlashAndFragment(): void
    {
        $this->assertSame(
            'https://example.com/pimcore-mcp',
            CanonicalUri::canonicalize('https://example.com/pimcore-mcp/#section')
        );
    }

    public function testDropsDefaultPortsButKeepsCustomPort(): void
    {
        $this->assertSame(
            'https://example.com/x',
            CanonicalUri::canonicalize('https://example.com:443/x')
        );
        $this->assertSame(
            'http://example.com/x',
            CanonicalUri::canonicalize('http://example.com:80/x')
        );
        $this->assertSame(
            'https://example.com:8443/x',
            CanonicalUri::canonicalize('https://example.com:8443/x')
        );
    }

    public function testEqualsIsNormalisationInsensitive(): void
    {
        $this->assertTrue(
            CanonicalUri::equals('https://Example.com/a/', 'https://example.com:443/a')
        );
        $this->assertFalse(
            CanonicalUri::equals('https://example.com/a', 'https://example.com/b')
        );
    }

    /**
     * @dataProvider canonicalOriginProvider
     */
    public function testAcceptsACanonicalOrigin(string $uri): void
    {
        $this->assertTrue(CanonicalUri::isCanonicalOrigin($uri));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function canonicalOriginProvider(): array
    {
        return [
            'https host' => ['https://pimcore.example.com'],
            'http localhost' => ['http://localhost'],
            'custom port' => ['http://localhost:8080'],
            'ipv4' => ['https://127.0.0.1'],
            'ipv6' => ['http://[::1]:8080'],
            'hyphenated label' => ['https://my-pimcore.example.com'],
        ];
    }

    /**
     * parse_url() accepts authorities no client resolves to the same host: it reads
     * "https://[::1" as host "[:" and port 1, and passes spaces and percent-encoding through.
     * canonicalize() rebuilds such a string unchanged, so comparing against it is not enough.
     *
     * @dataProvider malformedOriginProvider
     */
    public function testRejectsAMalformedOrigin(string $uri): void
    {
        $this->assertFalse(CanonicalUri::isCanonicalOrigin($uri));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function malformedOriginProvider(): array
    {
        return [
            'unbalanced ipv6 bracket' => ['https://[::1'],
            'doubled ipv6 bracket' => ['https://[[::1]]'],
            'bracketed non-address' => ['https://[zz]'],
            'unbracketed ipv6' => ['https://::1'],
            'space in host' => ['https://pim core.example.com'],
            'control character in host' => ["https://pim\x01core.example.com"],
            'backslash' => ['https://pimcore.example.com\\'],
            'percent-encoded host' => ['https://ex%41mple.com'],
            'empty label' => ['https://pimcore..example.com'],
            'label starting with a hyphen' => ['https://-pimcore.example.com'],
            'underscore' => ['https://pim_core.example.com'],
            'port zero' => ['https://pimcore.example.com:0'],
            'path' => ['https://pimcore.example.com/oauth'],
            'uppercase host' => ['https://PIMCORE.example.com'],
        ];
    }
}
