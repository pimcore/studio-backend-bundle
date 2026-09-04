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

/**
 * Holds a validated authorization request between the redirect to the consent
 * screen and the user's approval, keyed by an opaque id carried in the URL.
 * Short-lived; the entry is removed once the authorization completes.
 *
 * @internal
 */
final readonly class PendingAuthorizationStore
{
    private const string KEY_PREFIX = 'pimcore_oauth_pending_';

    /**
     * The exact shape {@see AuthorizeController} mints via bin2hex(random_bytes(32)).
     * Ids arrive from the request, so lookups reject anything else before it can
     * reach the cache key — otherwise a reserved character (e.g. ":") throws and a
     * traversal segment ("../…") escapes into other paths.
     */
    private const string ID_PATTERN = '/^[a-f0-9]{64}$/';

    public function __construct(
        private CacheItemPoolInterface $cache,
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

    public function remove(string $id): void
    {
        if (!$this->isValidId($id)) {
            return;
        }

        $this->cache->deleteItem(self::KEY_PREFIX . $id);
    }

    private function isValidId(string $id): bool
    {
        return preg_match(self::ID_PATTERN, $id) === 1;
    }
}
