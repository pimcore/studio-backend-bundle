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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Repository;

use Pimcore\Bundle\StudioBackendBundle\Entity\Mcp\McpAccessToken;

/**
 * @internal
 */
interface McpAccessTokenRepositoryInterface
{
    public function findByHash(string $tokenHash): ?McpAccessToken;

    public function findOneByReference(string $reference): ?McpAccessToken;

    public function save(McpAccessToken $token): void;

    public function deleteByReference(string $reference): void;

    public function deleteByUserId(int $userId): void;

    /**
     * One grouped query for all given users: the number of tokens still valid at `$now` and the
     * latest expiry among them. A user without a valid token has no entry.
     *
     * @param int[] $userIds
     *
     * @return array<int, array{active: int, latestExpiresAt: int}>
     */
    public function summarizeByUserIds(array $userIds, int $now): array;

    /** @return int number of rows deleted */
    public function deleteExpired(int $now): int;
}
