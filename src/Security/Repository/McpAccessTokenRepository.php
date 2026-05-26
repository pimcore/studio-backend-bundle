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

use Doctrine\ORM\EntityManagerInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Mcp\McpAccessToken;

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

    public function deleteExpired(int $now): int
    {
        return (int) $this->entityManager->createQuery(
            'DELETE FROM ' . McpAccessToken::class . ' t WHERE t.expiresAt < :now'
        )->setParameter('now', $now)->execute();
    }
}
