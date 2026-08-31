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

use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;

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
     * @throws UniqueTokenIdentifierConstraintViolationException on a duplicate identifier
     */
    public function persist(
        string $identifier,
        string $type,
        int $expiresAt,
        ?int $userId,
        ?string $clientId,
        ?string $resource = null
    ): void;

    /**
     * Records the protected resource a token is bound to. The record already exists by
     * the time the binding is known, because league persists the token as it issues it.
     */
    public function bindResource(string $identifier, ?string $resource): void;

    /**
     * The protected resource a previously persisted token was bound to, or null when it
     * was unbound or is unknown.
     */
    public function resourceFor(string $identifier): ?string;

    public function revoke(string $identifier): void;

    public function isRevoked(string $identifier): bool;

    public function deleteExpired(int $now): int;
}
