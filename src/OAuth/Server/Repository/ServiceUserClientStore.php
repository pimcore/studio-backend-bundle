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
use InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Entity\OAuth\OAuthClientRecord;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;

/**
 * @internal
 */
final readonly class ServiceUserClientStore implements ServiceUserClientStoreInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DynamicClientStoreInterface $dynamicClientStore,
    ) {
    }

    public function save(DynamicClient $client): void
    {
        if ($client->serviceUserId === null) {
            throw new InvalidArgumentException('A service-user client must name its service user.');
        }

        $this->dynamicClientStore->save($client);
    }

    public function findByServiceUserId(int $serviceUserId): ?DynamicClient
    {
        $record = $this->entityManager->getRepository(OAuthClientRecord::class)->findOneBy(
            ['serviceUserId' => $serviceUserId],
        );

        return $record === null ? null : $this->dynamicClientStore->find($record->getClientId());
    }

    public function deleteByServiceUserId(int $serviceUserId): void
    {
        $record = $this->entityManager->getRepository(OAuthClientRecord::class)->findOneBy(
            ['serviceUserId' => $serviceUserId],
        );
        if ($record === null) {
            return;
        }

        $this->entityManager->remove($record);
        $this->entityManager->flush();
    }

    public function replaceSecretHash(string $identifier, string $secretHash): void
    {
        $record = $this->entityManager->getRepository(OAuthClientRecord::class)->find($identifier);
        if ($record === null || $record->getServiceUserId() === null) {
            return;
        }

        $record->setSecretHash($secretHash);
        $this->entityManager->flush();
    }
}
