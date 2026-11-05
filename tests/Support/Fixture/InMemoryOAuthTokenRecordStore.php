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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Support\Fixture;

use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\TokenRecordStoreInterface;

/**
 * The token record store as the database behaves: rows keyed by identifier, a duplicate
 * refused, and the subject each row was issued for.
 *
 * @internal
 */
final class InMemoryOAuthTokenRecordStore implements TokenRecordStoreInterface
{
    /**
     * @var array<string, array{type: string, userId: int|null, resource: string|null, revoked: bool}>
     */
    private array $records = [];

    public function persist(
        string $identifier,
        string $type,
        int $expiresAt,
        ?int $userId,
        ?string $clientId,
        ?string $resource = null
    ): void {
        if (isset($this->records[$identifier])) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $this->records[$identifier] = [
            'type' => $type,
            'userId' => $userId,
            'resource' => $resource,
            'revoked' => false,
        ];
    }

    public function bindResource(string $identifier, ?string $resource): void
    {
        if (isset($this->records[$identifier])) {
            $this->records[$identifier]['resource'] = $resource;
        }
    }

    public function resourceFor(string $identifier): ?string
    {
        return $this->records[$identifier]['resource'] ?? null;
    }

    public function revoke(string $identifier): void
    {
        if (isset($this->records[$identifier])) {
            $this->records[$identifier]['revoked'] = true;
        }
    }

    public function isRevoked(string $identifier): bool
    {
        return $this->records[$identifier]['revoked'] ?? false;
    }

    public function revokeByUserId(int $userId): int
    {
        $revoked = 0;
        foreach ($this->records as $identifier => $record) {
            if ($record['userId'] === $userId && !$record['revoked']) {
                $this->records[$identifier]['revoked'] = true;
                ++$revoked;
            }
        }

        return $revoked;
    }

    public function deleteExpired(int $now): int
    {
        return 0;
    }

    /**
     * @return list<string>
     */
    public function identifiersOfType(string $type): array
    {
        $identifiers = [];
        foreach ($this->records as $identifier => $record) {
            if ($record['type'] === $type) {
                $identifiers[] = $identifier;
            }
        }

        return $identifiers;
    }

    /**
     * @return list<int|null>
     */
    public function userIdsOfType(string $type): array
    {
        $userIds = [];
        foreach ($this->records as $record) {
            if ($record['type'] === $type) {
                $userIds[] = $record['userId'];
            }
        }

        return $userIds;
    }
}
