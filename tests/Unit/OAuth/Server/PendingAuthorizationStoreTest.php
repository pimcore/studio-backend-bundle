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
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use function sys_get_temp_dir;

final class PendingAuthorizationStoreTest extends Unit
{
    // 64 lowercase hex, the shape AuthorizeController mints via bin2hex(random_bytes(32)).
    private const string VALID_ID = 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2';

    private const string OTHER_ID = 'b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3';

    private string $lockPath;

    public function _before(): void
    {
        // A directory of its own per test, so one test's flock files cannot answer another's.
        $this->lockPath = sys_get_temp_dir() . '/studio-oauth-lock-test-' . bin2hex(random_bytes(8));
    }

    public function _after(): void
    {
        foreach (glob($this->lockPath . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->lockPath);
    }

    public function testStoreGetRoundTripLeavesTheEntryInPlace(): void
    {
        $store = $this->store();
        $params = ['client_id' => 'studio-mcp', 'scope' => 'mcp:read', 'state' => 'xyz'];

        $store->store(self::VALID_ID, $params);

        // Reading is what the consent screen does, and a reload must not end the flow.
        $this->assertSame($params, $store->get(self::VALID_ID));
        $this->assertSame($params, $store->get(self::VALID_ID));
    }

    public function testConsumeHandsTheParametersOverExactlyOnce(): void
    {
        $store = $this->store();
        $params = ['client_id' => 'studio-mcp', 'scope' => 'mcp:read'];
        $store->store(self::VALID_ID, $params);

        $this->assertSame($params, $store->consume(self::VALID_ID));
        $this->assertNull($store->consume(self::VALID_ID));
        $this->assertNull($store->get(self::VALID_ID));
    }

    /**
     * The interleaving the race needs, forced rather than hoped for.
     *
     * The pool is decorated so that the moment the first consume() has taken the lock and is
     * about to read, a second consume() for the same id runs - which is exactly the window a
     * concurrent approval would occupy. The inner call must come back empty: if it did not,
     * two approvals would each hold a valid authorization request and each mint a code.
     *
     * This runs in one process, so it demonstrates the exclusion rather than true
     * parallelism - but flock is taken per file handle, so the inner acquire contends for
     * the real lock the same way a second worker would.
     */
    public function testAConcurrentClaimInsideTheWindowIsRefused(): void
    {
        $inner = new ArrayAdapter();
        $store = null;
        $secondClaim = 'not attempted';

        $pool = new class($inner) implements CacheItemPoolInterface {
            /** @var callable|null */
            public $onGetItem = null;

            public function __construct(private readonly CacheItemPoolInterface $decorated)
            {
            }

            public function getItem(string $key): CacheItemInterface
            {
                if ($this->onGetItem !== null) {
                    $callback = $this->onGetItem;
                    $this->onGetItem = null;
                    $callback();
                }

                return $this->decorated->getItem($key);
            }

            public function getItems(array $keys = []): iterable
            {
                return $this->decorated->getItems($keys);
            }

            public function hasItem(string $key): bool
            {
                return $this->decorated->hasItem($key);
            }

            public function clear(): bool
            {
                return $this->decorated->clear();
            }

            public function deleteItem(string $key): bool
            {
                return $this->decorated->deleteItem($key);
            }

            public function deleteItems(array $keys): bool
            {
                return $this->decorated->deleteItems($keys);
            }

            public function save(CacheItemInterface $item): bool
            {
                return $this->decorated->save($item);
            }

            public function saveDeferred(CacheItemInterface $item): bool
            {
                return $this->decorated->saveDeferred($item);
            }

            public function commit(): bool
            {
                return $this->decorated->commit();
            }
        };

        $store = $this->store($pool);
        $store->store(self::VALID_ID, ['client_id' => 'studio-mcp']);

        $pool->onGetItem = static function () use (&$store, &$secondClaim): void {
            $secondClaim = $store->consume(self::VALID_ID);
        };

        $first = $store->consume(self::VALID_ID);

        $this->assertSame(['client_id' => 'studio-mcp'], $first, 'The first claimer must get the parameters.');
        $this->assertNull($secondClaim, 'A second claim taken inside the first one\'s window must come back empty.');
    }

    /**
     * The lock is per id, so one pending authorization being claimed must not hold up
     * anybody else's.
     */
    public function testAClaimOnOneIdDoesNotBlockAnother(): void
    {
        $store = $this->store();
        $store->store(self::VALID_ID, ['client_id' => 'a']);
        $store->store(self::OTHER_ID, ['client_id' => 'b']);

        $lock = (new LockFactory(new FlockStore($this->lockPath)))
            ->createLock('pimcore_oauth_pending_claim_' . self::VALID_ID, ttl: null);
        $this->assertTrue($lock->acquire());

        try {
            $this->assertNull($store->consume(self::VALID_ID), 'A held id must not be claimable.');
            $this->assertSame(['client_id' => 'b'], $store->consume(self::OTHER_ID));
        } finally {
            $lock->release();
        }
    }

    public function testUnknownIdReturnsNull(): void
    {
        $unknown = 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef';

        $this->assertNull($this->store()->get($unknown));
        $this->assertNull($this->store()->consume($unknown));
    }

    /**
     * A malformed id from the request must resolve to "not found" (→ 404), never
     * reach the cache key (a reserved char like ":" would throw → 500), and never
     * carry path-traversal segments into the lookup. That holds for the claim too,
     * which additionally must not turn one into a lock-file name.
     *
     * @dataProvider malformedIds
     */
    public function testMalformedIdReturnsNullWithoutThrowing(string $id): void
    {
        $store = $this->store();

        $this->assertNull($store->get($id));
        $this->assertNull($store->consume($id));
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

    private function store(?CacheItemPoolInterface $pool = null): PendingAuthorizationStore
    {
        return new PendingAuthorizationStore(
            $pool ?? new ArrayAdapter(),
            new LockFactory(new FlockStore($this->lockPath)),
            600,
        );
    }
}
