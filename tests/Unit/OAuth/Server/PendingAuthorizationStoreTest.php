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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\PendingAuthorizationStore;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class PendingAuthorizationStoreTest extends Unit
{
    // 64 lowercase hex, the shape AuthorizeController mints via bin2hex(random_bytes(32)).
    private const string VALID_ID = 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2';

    public function testStoreGetRemoveRoundTrip(): void
    {
        $store = new PendingAuthorizationStore(new ArrayAdapter(), 600);
        $params = ['client_id' => 'studio-mcp', 'scope' => 'mcp:read', 'state' => 'xyz'];

        $store->store(self::VALID_ID, $params);
        $this->assertSame($params, $store->get(self::VALID_ID));

        $store->remove(self::VALID_ID);
        $this->assertNull($store->get(self::VALID_ID));
    }

    public function testUnknownIdReturnsNull(): void
    {
        $unknown = 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef';
        $this->assertNull((new PendingAuthorizationStore(new ArrayAdapter(), 600))->get($unknown));
    }

    /**
     * A malformed id from the request must resolve to "not found" (→ 404), never
     * reach the cache key (a reserved char like ":" would throw → 500), and never
     * carry path-traversal segments into the lookup.
     *
     * @dataProvider malformedIds
     */
    public function testMalformedIdReturnsNullWithoutThrowing(string $id): void
    {
        $store = new PendingAuthorizationStore(new ArrayAdapter(), 600);

        $this->assertNull($store->get($id));
    }

    public function testRemoveIgnoresMalformedId(): void
    {
        $store = new PendingAuthorizationStore(new ArrayAdapter(), 600);

        // Must not throw on the reserved-character key.
        $store->remove('foo:bar');
        $this->assertNull($store->get('foo:bar'));
    }

    /**
     * @return array<string, array{string}>
     */
    public function malformedIds(): array
    {
        return [
            'reserved colon (would 500)' => ['foo:bar'],
            'path traversal'             => ['../../execution-engine/abort/1'],
            'slash'                      => ['a/b'],
            'uppercase hex'              => [strtoupper(self::VALID_ID)],
            'too short'                  => ['abc'],
            'too long'                   => [self::VALID_ID . 'a'],
            'non-hex chars'              => [str_repeat('g', 64)],
            'empty'                      => [''],
        ];
    }
}
