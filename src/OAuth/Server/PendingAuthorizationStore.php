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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Lock\LockFactory;
use function preg_match;

/**
 * Holds a validated authorization request between the redirect to the consent
 * screen and the user's approval, keyed by an opaque id carried in the URL.
 * Short-lived; the entry is removed once the authorization completes.
 *
 * @internal
 */
final readonly class PendingAuthorizationStore implements PendingAuthorizationStoreInterface
{
    private const string KEY_PREFIX = 'pimcore_oauth_pending_';

    /**
     * The exact shape {@see AuthorizeController} mints via bin2hex(random_bytes(32)).
     * Ids arrive from the request, so lookups reject anything else before it can
     * reach the cache key — otherwise a reserved character (e.g. ":") throws and a
     * traversal segment ("../…") escapes into other paths.
     */
    private const string ID_PATTERN = '/^[a-f0-9]{64}$/u';

    /**
     * Separate namespace from the cache key, so a lock file can never be mistaken for an
     * entry and the two cannot collide on the id.
     */
    private const string LOCK_PREFIX = 'pimcore_oauth_pending_claim_';

    public function __construct(
        private CacheItemPoolInterface $cache,
        private LockFactory $lockFactory,
        private int $ttl,
    ) {
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    public function store(string $id, array $queryParams): void
    {
        $item = $this->cache->getItem(self::KEY_PREFIX . $id);
        $item->set($queryParams)->expiresAfter($this->ttl);
        $this->cache->save($item);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $id): ?array
    {
        if (!$this->isValidId($id)) {
            return null;
        }

        $item = $this->cache->getItem(self::KEY_PREFIX . $id);

        return $item->isHit() ? $item->get() : null;
    }

    /**
     * Read and delete under a per-id lock, so two concurrent approvals of one authorization
     * cannot both be handed the parameters and go on to mint a code each.
     *
     * The lock is what makes this a claim; the cache pool cannot. PSR-6 has no atomic
     * take, and the pool behind this store is a filesystem adapter whose deleteItem()
     * answers true when the entry was already gone
     * (FilesystemCommonTrait::doDelete() - `!is_file($file) || ... || !file_exists($file)`),
     * so "my delete succeeded" says nothing about whether this caller is the one that
     * removed it. Both racers would see true.
     *
     * Non-blocking on purpose: a second caller on the same id has nothing to wait for. Once
     * the holder is done the entry is gone, so waiting only turns an immediate 404 into a
     * delayed one. Losing the race is refused here, before league is asked for anything.
     *
     * Scope: flock over the same directory tree the pool writes into, so the lock reaches
     * exactly as far as the data does - two workers that can both see a pending
     * authorization share the filesystem holding it, and the same flock serialises them.
     * Workers that do not share it cannot see each other's entries to race over. No TTL:
     * flock is held by the file handle, so the kernel releases it if a worker dies
     * mid-claim, which is the self-healing a time-based lease only approximates.
     *
     * @return array<string, mixed>|null
     */
    public function consume(string $id): ?array
    {
        if (!$this->isValidId($id)) {
            return null;
        }

        $lock = $this->lockFactory->createLock(self::LOCK_PREFIX . $id, ttl: null);
        if (!$lock->acquire()) {
            return null;
        }

        try {
            $item = $this->cache->getItem(self::KEY_PREFIX . $id);
            if (!$item->isHit()) {
                return null;
            }

            $this->cache->deleteItem(self::KEY_PREFIX . $id);

            return $item->get();
        } finally {
            $lock->release();
        }
    }

    private function isValidId(string $id): bool
    {
        return preg_match(self::ID_PATTERN, $id) === 1;
    }
}
