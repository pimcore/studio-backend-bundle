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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service;

use InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Repository\McpServerConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ServiceUserClientStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\TokenRecordStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\McpAccessTokenServiceInterface;
use Pimcore\Model\User;
use Psr\Log\LoggerInterface;
use function count;

/**
 * @internal
 */
final readonly class AccessRevocationService implements AccessRevocationServiceInterface
{
    public function __construct(
        private ServiceUserClientStoreInterface $clientStore,
        private TokenRecordStoreInterface $tokenRecordStore,
        private McpAccessTokenServiceInterface $mcpAccessTokenService,
        private McpServerConfigRepositoryInterface $mcpServerRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws NotFoundException
     */
    public function revokeAll(User $serviceUser): void
    {
        if (!$serviceUser->isServiceUser()) {
            throw new InvalidArgumentException('Expected a service user, got a person: ' . (string) $serviceUser->getName());
        }

        $id = $serviceUser->getId();
        if ($id !== null) {
            $this->clientStore->deleteByServiceUserId($id);
            $this->tokenRecordStore->revokeByUserId($id);
            $this->mcpAccessTokenService->revokeByUser($id);
        }

        $name = $serviceUser->getName();
        if ($name !== null && $name !== '') {
            $this->removeMcpServerAccess($name);
        }
    }

    /**
     * @throws NotFoundException
     */
    private function removeMcpServerAccess(string $name): void
    {
        foreach ($this->mcpServerRepository->list() as $server) {
            $access = $server->access;
            $sharedUsers = $this->withoutName($access->sharedUsers, $name);
            $isOwner = $access->owner === $name;
            if (!$isOwner && count($sharedUsers) === count($access->sharedUsers)) {
                continue;
            }

            try {
                $this->mcpServerRepository->save(new McpServerDefinition(
                    id: $server->id,
                    displayName: $server->displayName,
                    description: $server->description,
                    toolIds: $server->toolIds,
                    scopes: $server->scopes,
                    enabled: $server->enabled,
                    access: new McpServerAccess(
                        owner: $isOwner ? null : $access->owner,
                        shareGlobal: $access->shareGlobal,
                        sharedUsers: $sharedUsers,
                        sharedRoles: $access->sharedRoles,
                    ),
                ));
            } catch (NotWriteableException) {
                // A server shipped in read-only configuration cannot be changed here. The
                // operator has to remove the entry from that configuration.
                $this->logger->warning(
                    'The access entry of a deleted service user could not be removed from the read-only MCP server.',
                    ['server' => $server->id, 'serviceUser' => $name]
                );
            }
        }
    }

    /**
     * @param list<McpServerAccessEntry> $entries
     *
     * @return list<McpServerAccessEntry>
     */
    private function withoutName(array $entries, string $name): array
    {
        $kept = [];
        foreach ($entries as $entry) {
            if ($entry->name !== $name) {
                $kept[] = $entry;
            }
        }

        return $kept;
    }
}
