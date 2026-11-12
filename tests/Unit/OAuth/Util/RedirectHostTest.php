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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\RedirectHost;

final class RedirectHostTest extends Unit
{
    /**
     * @dataProvider uris
     */
    public function testFromUri(?string $uri, ?string $expected): void
    {
        $this->assertSame($expected, RedirectHost::fromUri($uri));
    }

    /**
     * @return array<string, array{?string, ?string}>
     */
    public function uris(): array
    {
        return [
            'loopback with port'   => ['http://localhost:6274/oauth/callback', 'localhost:6274'],
            'https no port'        => ['https://app.example/cb', 'app.example'],
            'explicit port'        => ['https://app.example:8443/cb', 'app.example:8443'],
            'ip literal loopback'  => ['http://127.0.0.1:33418/callback', '127.0.0.1:33418'],
            'null'                 => [null, null],
            'empty'                => ['', null],
            'no host (path only)'  => ['/just/a/path', null],
            'urn without host'     => ['urn:ietf:wg:oauth', null],
        ];
    }
}
