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

use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use Pimcore\Bundle\StudioBackendBundle\Entity\OAuth\OAuthTokenRecord;
use function time;

/**
 * Doctrine-backed {@see TokenRecordStoreInterface}.
 *
 * @internal
 */
final readonly class TokenRecordStore implements TokenRecordStoreInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function persist(string $identifier, string $type, int $expiresAt, ?int $userId, ?string $clientId): void
    {
        if ($this->find($identifier) !== null) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $this->entityManager->persist(
            new OAuthTokenRecord($identifier, $type, $expiresAt, $userId, $clientId, time())
        );
        $this->entityManager->flush();
    }

    public function revoke(string $identifier): void
    {
        $record = $this->find($identifier);
        if ($record === null) {
            return;
        }

        $record->setRevoked(true);
        $this->entityManager->flush();
    }

    public function isRevoked(string $identifier): bool
    {
        $record = $this->find($identifier);

        return $record !== null && $record->isRevoked();
    }

    public function deleteExpired(int $now): int
    {
        return (int) $this->entityManager->createQuery(
            'DELETE FROM ' . OAuthTokenRecord::class . ' t WHERE t.expiresAt < :now'
        )->setParameter('now', $now)->execute();
    }

    private function find(string $identifier): ?OAuthTokenRecord
    {
        return $this->entityManager->getRepository(OAuthTokenRecord::class)->find($identifier);
    }
}
