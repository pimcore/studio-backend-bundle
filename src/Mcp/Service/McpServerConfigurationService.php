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

use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
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
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServerUserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerAccessResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerCapability;
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
        private UserResolverInterface $userResolver,
        private ?string $issuer,
    ) {
    }

    public function listConfigurations(): array
    {
        $user = $this->securityService->getCurrentUser();

        $servers = [];
        foreach ($this->repository->list() as $definition) {
            if ($this->accessResolver->isAllowed($definition, McpServerCapability::View, $user)) {
                $servers[] = $this->buildServer($definition);
            }
        }

        return $servers;
    }

    public function getConfiguration(string $id): McpServer
    {
        $definition = $this->repository->get($id);
        $this->assert($definition, McpServerCapability::View);

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
            $this->securityService->getCurrentUser()->getName()
        );
        $this->repository->save($definition);

        return $this->buildServer($definition);
    }

    public function updateConfiguration(string $id, McpServerParameter $parameter): McpServer
    {
        // Editing (incl. re-sharing) requires the Edit capability; the slug is
        // locked to the id and the original owner is preserved.
        $existing = $this->repository->get($id);
        $this->assert($existing, McpServerCapability::Edit);

        $definition = $this->buildDefinition($id, $parameter, $existing->access->owner);
        $this->repository->save($definition);

        return $this->buildServer($definition);
    }

    public function deleteConfiguration(string $id): void
    {
        $definition = $this->repository->get($id);
        $this->assert($definition, McpServerCapability::Edit);

        $this->repository->delete($id);
    }

    private function buildDefinition(string $id, McpServerParameter $parameter, ?string $owner): McpServerDefinition
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
                // The owner is not seeded into the grid: they get implicit read + edit
                // via the resolver, and must grant themselves Access explicitly. User
                // grants are patched so admins and the owner are never persisted as
                // read-only or non-editable (see normalizeUserEntries).
                sharedUsers: $this->normalizeUserEntries($parameter->getSharedUsers(), $owner),
                sharedRoles: $this->normalizeEntries($parameter->getSharedRoles()),
            ),
        );
    }

    private function buildServer(McpServerDefinition $definition): McpServer
    {
        $resolved = $this->accessResolver->resolve($definition, $this->securityService->getCurrentUser());

        $server = $this->serverHydrator->hydrate(
            $definition,
            $this->buildUrl($definition->urlSlug),
            $this->deriveScopes($definition->toolIds),
            $this->repository->isWriteable(),
            new McpServerUserPermissions($resolved['view'], $resolved['access'], $resolved['edit']),
        );

        $this->eventDispatcher->dispatch(new McpServerEvent($server), McpServerEvent::EVENT_NAME);

        return $server;
    }

    /**
     * @throws ForbiddenException
     */
    private function assert(McpServerDefinition $definition, McpServerCapability $capability): void
    {
        if (!$this->accessResolver->isAllowed($definition, $capability, $this->securityService->getCurrentUser())) {
            throw new ForbiddenException(
                sprintf('You are not allowed to %s the MCP server "%s".', $capability->value, $definition->id)
            );
        }
    }

    /**
     * User grants, with the admin/owner capabilities enforced. Admins and the
     * owner always hold Config Read + Edit at resolve time; persisting them any
     * other way would only be a lie the UI then faithfully renders (showing the
     * server as non-editable). So we patch each user entry on write rather than
     * trusting the client to have disabled the right checkboxes — this stays
     * correct even when the frontend has a bug or the owner is somehow submitted
     * as read-only. Access is never touched: it is explicit for everyone.
     *
     * @param list<array{name?: mixed, canRead?: mixed, canAccess?: mixed, canEdit?: mixed}> $raw
     *
     * @return list<McpServerAccessEntry>
     */
    private function normalizeUserEntries(array $raw, ?string $owner): array
    {
        $entries = [];
        foreach ($raw as $item) {
            $entry = McpServerAccessEntry::fromMixed($item);
            if ($entry === null) {
                continue;
            }

            $entries[] = $this->enforceImplicitEdit($entry, $owner);
        }

        return $entries;
    }

    /**
     * Force Read + Edit on a user entry that belongs to an admin or the owner,
     * preserving its Access flag. Leaves a non-privileged entry untouched.
     */
    private function enforceImplicitEdit(McpServerAccessEntry $entry, ?string $owner): McpServerAccessEntry
    {
        // canEdit already implies canRead (see McpServerAccessEntry), so an entry
        // that can edit needs no patching.
        if ($entry->canEdit || !$this->isImplicitEditor($entry->name, $owner)) {
            return $entry;
        }

        return new McpServerAccessEntry(
            $entry->name,
            canRead: true,
            canAccess: $entry->canAccess,
            canEdit: true,
        );
    }

    /**
     * Whether the named user gets Read + Edit implicitly — i.e. is the owner or
     * an admin. Mirrors the owner/admin rules in {@see McpServerAccessResolver}.
     */
    private function isImplicitEditor(string $name, ?string $owner): bool
    {
        if ($owner !== null && $owner !== '' && $name === $owner) {
            return true;
        }

        return $this->userResolver->getByName($name)?->isAdmin() === true;
    }

    /**
     * @param list<array{name?: mixed, canRead?: mixed, canAccess?: mixed, canEdit?: mixed}> $raw
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
