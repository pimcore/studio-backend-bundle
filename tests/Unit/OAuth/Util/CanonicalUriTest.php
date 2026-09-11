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
}
