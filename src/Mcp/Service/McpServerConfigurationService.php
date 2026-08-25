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
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Event\PreResponse\McpServerEvent;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Hydrator\McpServerHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\MappedParameter\McpServerParameter;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Repository\McpServerConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServer;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function array_keys;
use function array_map;
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
        private SecurityServiceInterface $securityService,
        private ?string $issuer,
    ) {
    }

    public function listConfigurations(): array
    {
        return array_map(
            fn (McpServerDefinition $definition): McpServer => $this->buildServer($definition),
            $this->repository->list()
        );
    }

    public function getConfiguration(string $id): McpServer
    {
        return $this->buildServer($this->repository->get($id));
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
        // Preserve the original owner; the slug is locked to the id.
        $existing = $this->repository->get($id);
        $definition = $this->buildDefinition($id, $parameter, $existing->access->owner);
        $this->repository->save($definition);

        return $this->buildServer($definition);
    }

    public function deleteConfiguration(string $id): void
    {
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
                sharedUsers: $parameter->getSharedUsers(),
                sharedRoles: $parameter->getSharedRoles(),
            ),
        );
    }

    private function buildServer(McpServerDefinition $definition): McpServer
    {
        $server = $this->serverHydrator->hydrate(
            $definition,
            $this->buildUrl($definition->urlSlug),
            $this->deriveScopes($definition->toolIds),
            $this->repository->isWriteable(),
        );

        $this->eventDispatcher->dispatch(new McpServerEvent($server), McpServerEvent::EVENT_NAME);

        return $server;
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
                $scopes[$tool->getDefinition()->requiredScope()] = true;
            }
        }

        return array_keys($scopes);
    }

    private function buildUrl(string $slug): string
    {
        return rtrim($this->issuer ?? '', '/') . '/pimcore-mcp/studio/' . $slug;
    }
}
