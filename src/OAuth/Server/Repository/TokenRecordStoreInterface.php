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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository;

/**
 * Tracking store for issued OAuth artifacts, backing revocation and reuse
 * detection. Revocation is a blocklist: an identifier not on record counts as
 * not revoked, so validly-signed tokens stay acceptable unless explicitly
 * revoked.
 *
 * @internal
 */
interface TokenRecordStoreInterface
{
    /**
     * @throws \League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException on a duplicate identifier
     */
    public function persist(string $identifier, string $type, int $expiresAt, ?int $userId, ?string $clientId): void;

    public function revoke(string $identifier): void;

    public function isRevoked(string $identifier): bool;

    public function deleteExpired(int $now): int;
}
