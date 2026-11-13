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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Mcp\McpAccessToken;
use function array_values;

/**
 * @internal
 */
final readonly class McpAccessTokenRepository implements McpAccessTokenRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findByHash(string $tokenHash): ?McpAccessToken
    {
        return $this->entityManager->getRepository(McpAccessToken::class)->find($tokenHash);
    }

    public function findOneByReference(string $reference): ?McpAccessToken
    {
        return $this->entityManager->getRepository(McpAccessToken::class)
            ->findOneBy(['reference' => $reference]);
    }

    public function save(McpAccessToken $token): void
    {
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }

    public function deleteByReference(string $reference): void
    {
        $this->entityManager->createQuery(
            'DELETE FROM ' . McpAccessToken::class . ' t WHERE t.reference = :reference'
        )->setParameter('reference', $reference)->execute();
    }

    public function deleteByUserId(int $userId): void
    {
        $this->entityManager->createQuery(
            'DELETE FROM ' . McpAccessToken::class . ' t WHERE t.userId = :userId'
        )->setParameter('userId', $userId)->execute();
    }

    public function summarizeByUserIds(array $userIds, int $now): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = $this->entityManager->createQuery(
            'SELECT t.userId AS userId, COUNT(t.tokenHash) AS active, MAX(t.expiresAt) AS latestExpiresAt '
            . 'FROM ' . McpAccessToken::class . ' t '
            . 'WHERE t.userId IN (:userIds) AND t.expiresAt > :now GROUP BY t.userId'
        )
            ->setParameter('userIds', array_values($userIds), ArrayParameterType::INTEGER)
            ->setParameter('now', $now)
            ->getArrayResult();

        $summaries = [];
        foreach ($rows as $row) {
            $summaries[(int) $row['userId']] = [
                'active' => (int) $row['active'],
                'latestExpiresAt' => (int) $row['latestExpiresAt'],
            ];
        }

        return $summaries;
    }

    public function deleteExpired(int $now): int
    {
        return (int) $this->entityManager->createQuery(
            'DELETE FROM ' . McpAccessToken::class . ' t WHERE t.expiresAt < :now'
        )->setParameter('now', $now)->execute();
    }
}
