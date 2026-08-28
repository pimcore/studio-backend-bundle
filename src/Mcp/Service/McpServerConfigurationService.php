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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Event\PreResponse\McpServerEvent;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Hydrator\McpServerHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\MappedParameter\McpServerParameter;
use Pimcore\Bundle\StudioBackendBundle\Mcp\McpScopes;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Repository\McpServerConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServer;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerAccessResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerPermission;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function array_keys;
use function rtrim;
use function sprintf;

/**
 * @internal
 */
final readonly class McpServerConfigurationService implements McpServerConfigurationServiceInterface
{
    public function __construct(
        private McpServerHydratorInterface $serverHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private McpServerConfigRepositoryInterface $repository,
        private McpToolRegistryInterface $toolRegistry,
        private McpServerAccessResolverInterface $accessResolver,
        private SecurityServiceInterface $securityService,
        private ?string $issuer,
    ) {
    }

    public function listConfigurations(): array
    {
        $user = $this->securityService->getCurrentUser();

        $servers = [];
        foreach ($this->repository->list() as $definition) {
            if ($this->accessResolver->isAllowed($definition, McpServerPermission::Read, $user)) {
                $servers[] = $this->buildServer($definition);
            }
        }

        return $servers;
    }

    public function getConfiguration(string $id): McpServer
    {
        $definition = $this->repository->get($id);
        $this->assert($definition, McpServerPermission::Read);

        return $this->buildServer($definition);
    }

    public function saveConfiguration(McpServerParameter $parameter): McpServer
    {
        $id = $parameter->getUrlSlug();
        if ($this->repository->has($id)) {
            throw new ElementExistsException(
                sprintf('An MCP server with the id "%s" already exists.', $id)
            );
        }

        $definition = $this->buildDefinition(
            $id,
            $parameter,
            $this->securityService->getCurrentUser()->getId()
        );
        $this->repository->save($definition);

        return $this->buildServer($definition);
    }

    public function updateConfiguration(string $id, McpServerParameter $parameter): McpServer
    {
        // Editing (incl. re-sharing) requires write; the slug is locked to the id
        // and the original owner is preserved.
        $existing = $this->repository->get($id);
        $this->assert($existing, McpServerPermission::Write);

        $definition = $this->buildDefinition($id, $parameter, $existing->access->owner);
        $this->repository->save($definition);

        return $this->buildServer($definition);
    }

    public function deleteConfiguration(string $id): void
    {
        $definition = $this->repository->get($id);
        $this->assert($definition, McpServerPermission::Write);

        $this->repository->delete($id);
    }

    private function buildDefinition(string $id, McpServerParameter $parameter, ?int $owner): McpServerDefinition
    {
        return new McpServerDefinition(
            id: $id,
            displayName: $parameter->getName(),
            description: $parameter->getDescription() ?? '',
            urlSlug: $id,
            toolIds: $parameter->getTools(),
            scopes: $this->deriveScopes($parameter->getTools()),
            enabled: $parameter->isEnabled(),
            access: new McpServerAccess(
                owner: $owner,
                shareGlobal: $parameter->shareGlobal(),
                sharedUsers: $this->normalizeEntries($parameter->getSharedUsers()),
                sharedRoles: $this->normalizeEntries($parameter->getSharedRoles()),
            ),
        );
    }

    private function buildServer(McpServerDefinition $definition): McpServer
    {
        $permissions = $this->accessResolver->resolve($definition, $this->securityService->getCurrentUser());

        $server = $this->serverHydrator->hydrate(
            $definition,
            $this->buildUrl($definition->urlSlug),
            $this->deriveScopes($definition->toolIds),
            $this->repository->isWriteable(),
            $permissions['read'],
            $permissions['write'],
        );

        $this->eventDispatcher->dispatch(new McpServerEvent($server), McpServerEvent::EVENT_NAME);

        return $server;
    }

    /**
     * @throws ForbiddenException
     */
    private function assert(McpServerDefinition $definition, McpServerPermission $permission): void
    {
        if (!$this->accessResolver->isAllowed($definition, $permission, $this->securityService->getCurrentUser())) {
            throw new ForbiddenException(
                sprintf('You are not allowed to %s the MCP server "%s".', $permission->value, $definition->id)
            );
        }
    }

    /**
     * @param list<array{id?: mixed, permission?: mixed}> $raw
     *
     * @return list<McpServerAccessEntry>
     */
    private function normalizeEntries(array $raw): array
    {
        $entries = [];
        foreach ($raw as $item) {
            $entry = McpServerAccessEntry::fromMixed($item);
            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * @param list<string> $toolIds
     *
     * @return list<string>
     */
    private function deriveScopes(array $toolIds): array
    {
        $scopes = [];
        foreach ($toolIds as $toolId) {
            $tool = $this->toolRegistry->get($toolId);
            if ($tool !== null) {
                $scopes[McpScopes::forReadOnly($tool->isReadOnly())] = true;
            }
        }

        return array_keys($scopes);
    }

    private function buildUrl(string $slug): string
    {
        return rtrim($this->issuer ?? '', '/') . '/pimcore-mcp/studio/' . $slug;
    }
}
