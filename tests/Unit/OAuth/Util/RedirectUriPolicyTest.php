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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\RedirectUriPolicy;

final class RedirectUriPolicyTest extends Unit
{
    /**
     * @return array<string, array{string}>
     */
    private function acceptable(): array
    {
        return [
            'https' => ['https://app.example/cb'],
            'https with port and query' => ['https://app.example:8443/cb?x=1'],
            'http on 127.0.0.1' => ['http://127.0.0.1:8137/cb'],
            'http on localhost' => ['http://localhost:8137/cb'],
            'http on IPv6 loopback' => ['http://[::1]:8137/cb'],
            'uppercase scheme' => ['HTTPS://app.example/cb'],
            'uppercase loopback host' => ['http://LOCALHOST:8137/cb'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    private function unacceptable(): array
    {
        return [
            'cleartext on a routable host' => ['http://attacker.example/cb'],
            'cleartext on a subdomain of loopback' => ['http://localhost.attacker.example/cb'],
            'fragment' => ['https://app.example/cb#x'],
            'relative' => ['/cb'],
            'scheme only' => ['https://'],
            'empty' => [''],
            'custom scheme' => ['com.example.app://cb'],
            'doubled IPv6 brackets' => ['http://[[::1]]/cb'],
            'unbalanced IPv6 brackets' => ['http://[::1]]/cb'],
            'backslash authority differential' => ['http://attacker.example\\@localhost/cb'],
            'backslash before a loopback literal' => ['http://attacker.example\\@127.0.0.1/cb'],
            'userinfo on a loopback host' => ['http://user@localhost/cb'],
            'userinfo with password' => ['http://user:pw@localhost/cb'],
            'tab inside the authority' => ["http://local\thost/cb"],
            'newline inside the authority' => ["http://local\nhost/cb"],
            'space in the uri' => ['http://local host/cb'],
        ];
    }

    public function testAcceptsHttpsAndLoopbackHttp(): void
    {
        foreach ($this->acceptable() as $case => [$uri]) {
            $this->assertTrue(RedirectUriPolicy::isAcceptable($uri), $case);
        }
    }

    public function testRejectsEverythingElse(): void
    {
        foreach ($this->unacceptable() as $case => [$uri]) {
            $this->assertFalse(RedirectUriPolicy::isAcceptable($uri), $case);
        }
    }
}
