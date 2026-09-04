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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Server\RedirectUri;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\RedirectUri\LoopbackRedirectUriValidator;

final class LoopbackRedirectUriValidatorTest extends Unit
{
    public function testExactMatchForNonLoopback(): void
    {
        $validator = new LoopbackRedirectUriValidator(['https://app.example/cb']);
        $this->assertTrue($validator->validateRedirectUri('https://app.example/cb'));
        $this->assertFalse($validator->validateRedirectUri('https://app.example/other'));
        $this->assertFalse($validator->validateRedirectUri('https://evil.example/cb'));
    }

    public function testLocalhostMatchesRegardlessOfPort(): void
    {
        // The case that unblocks CLI clients: registered without a port, requested
        // on an ephemeral port.
        $validator = new LoopbackRedirectUriValidator(['http://localhost/callback']);
        $this->assertTrue($validator->validateRedirectUri('http://localhost:3118/callback'));
        $this->assertTrue($validator->validateRedirectUri('http://localhost/callback'));
    }

    public function testIpLoopbackMatchesRegardlessOfPort(): void
    {
        $validator = new LoopbackRedirectUriValidator(['http://127.0.0.1/callback', 'http://[::1]/callback']);
        $this->assertTrue($validator->validateRedirectUri('http://127.0.0.1:52000/callback'));
        $this->assertTrue($validator->validateRedirectUri('http://[::1]:52000/callback'));
    }

    public function testLoopbackStillRequiresSameHostAndPath(): void
    {
        $validator = new LoopbackRedirectUriValidator(['http://localhost/callback']);
        // Different path must not match even for loopback.
        $this->assertFalse($validator->validateRedirectUri('http://localhost:3118/evil'));
        // Different loopback host must not match a localhost registration.
        $this->assertFalse($validator->validateRedirectUri('http://127.0.0.1:3118/callback'));
    }

    public function testHttpsLoopbackIsExactMatchOnly(): void
    {
        // The port exception applies only to http loopback (RFC 8252); https keeps
        // exact matching, so a differing port must fail.
        $validator = new LoopbackRedirectUriValidator(['https://localhost/callback']);
        $this->assertFalse($validator->validateRedirectUri('https://localhost:3118/callback'));
        $this->assertTrue($validator->validateRedirectUri('https://localhost/callback'));
    }

    public function testLocalhostDisabledFallsBackToExactMatch(): void
    {
        // RFC-strict mode: localhost is no longer treated as loopback, so a port
        // mismatch fails, while the IP literals keep the port exception.
        $validator = new LoopbackRedirectUriValidator(
            ['http://localhost/callback', 'http://127.0.0.1/callback'],
            false,
        );
        $this->assertFalse($validator->validateRedirectUri('http://localhost:3118/callback'));
        $this->assertTrue($validator->validateRedirectUri('http://127.0.0.1:3118/callback'));
        $this->assertTrue($validator->validateRedirectUri('http://localhost/callback'));
    }
}
