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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Service;

use Pimcore\Bundle\StudioBackendBundle\Security\Dto\ValidatedAccessToken;

/**
 * Issues, refreshes, revokes and validates dynamic MCP access tokens.
 * `issue()` and `refresh()` resolve identity server-side; never expose to HTTP callers
 * a way to pass an arbitrary userId.
 *
 * @internal
 */
interface McpAccessTokenServiceInterface
{
    /**
     * Mint a fresh token bound to $userId, return the plaintext value (returned only here).
     * Replaces any existing token row for the same `$reference` to enforce
     * exactly-one-live-token-per-chat-session.
     *
     * @throws \Pimcore\Bundle\StudioBackendBundle\Security\Exception\McpTokenUserInvalidException
     */
    public function issue(int $userId, int $ttlSeconds, string $reference): string;

    /**
     * Extend the expiry of the live token for $reference (value unchanged).
     * Returns false if no live, user-valid token exists (caller should re-issue).
     * Revokes the row if the bound user is no longer valid.
     */
    public function refresh(string $reference, int $ttlSeconds): bool;

    public function revoke(string $reference): void;

    public function revokeByUser(int $userId): void;

    /** Resolve a plaintext token to its user + reference, or null if invalid/expired. */
    public function validate(string $token): ?ValidatedAccessToken;
}
