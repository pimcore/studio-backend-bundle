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
use Pimcore\Bundle\StudioBackendBundle\Entity\OAuth\OAuthClientRecord;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;

/**
 * Doctrine-backed {@see DynamicClientStoreInterface}.
 *
 * @internal
 */
final readonly class DynamicClientStore implements DynamicClientStoreInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(DynamicClient $client): void
    {
        $this->entityManager->persist(
            new OAuthClientRecord(
                $client->identifier,
                $client->name,
                $client->redirectUris,
                $client->grantTypes,
                $client->scopes,
                $client->confidential,
                $client->secretHash,
                $client->metadataHash,
            )
        );
        $this->entityManager->flush();
    }

    public function find(string $identifier): ?DynamicClient
    {
        $record = $this->entityManager->getRepository(OAuthClientRecord::class)->find($identifier);

        return $record === null ? null : $this->toDto($record);
    }

    public function findByMetadataHash(string $metadataHash): ?DynamicClient
    {
        // Oldest first, so a repeat registration keeps resolving to the same client_id
        // even if duplicates predate this lookup.
        $record = $this->entityManager->getRepository(OAuthClientRecord::class)->findOneBy(
            ['metadataHash' => $metadataHash],
            ['createdAt' => 'ASC'],
        );

        return $record === null ? null : $this->toDto($record);
    }

    private function toDto(OAuthClientRecord $record): DynamicClient
    {
        return new DynamicClient(
            $record->getClientId(),
            $record->getName(),
            $record->getRedirectUris(),
            $record->getGrantTypes(),
            $record->getScopes(),
            $record->isConfidential(),
            $record->getSecretHash(),
            $record->getMetadataHash(),
            $record->getCreatedAt(),
        );
    }
}
