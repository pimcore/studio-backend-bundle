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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Hydrator\McpServerHydrator;
use Pimcore\Bundle\StudioBackendBundle\Mcp\MappedParameter\McpServerParameter;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Repository\McpServerConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Service\McpServerConfigurationService;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolAnnotations;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class McpServerConfigurationServiceTest extends Unit
{
    private const ISSUER = 'https://example.test/';

    public function testSaveConfigurationSetsOwnerDerivesScopesAndPersists(): void
    {
        $repository = $this->repository();
        $service = $this->service($repository, currentUserId: 42);

        $server = $service->saveConfiguration($this->parameter('objects-read', tools: ['get_car_info']));

        $this->assertSame('objects-read', $server->getId());
        $this->assertSame(42, $server->getOwnerId());
        $this->assertSame(['mcp:read'], $server->getScopes());
        $this->assertSame('https://example.test/pimcore-mcp/studio/objects-read', $server->getUrl());
        $this->assertTrue($repository->has('objects-read'));
        $this->assertSame(42, $repository->get('objects-read')->access->owner);
    }

    public function testSaveConfigurationDerivesScopeUnionFromAllTools(): void
    {
        $service = $this->service($this->repository(), currentUserId: 1);

        $server = $service->saveConfiguration(
            $this->parameter('mixed', tools: ['get_car_info', 'delete_object'])
        );

        $this->assertSame(['mcp:read', 'mcp:write'], $server->getScopes());
    }

    public function testSaveConfigurationThrowsWhenSlugAlreadyExists(): void
    {
        $repository = $this->repository(['taken' => $this->definition('taken', owner: 5)]);
        $service = $this->service($repository, currentUserId: 42);

        $this->expectException(ElementExistsException::class);

        $service->saveConfiguration($this->parameter('taken'));
    }

    public function testUpdateConfigurationPreservesOwnerAndLocksSlug(): void
    {
        $repository = $this->repository(['srv' => $this->definition('srv', owner: 99)]);
        $service = $this->service($repository, currentUserId: 42);

        $server = $service->updateConfiguration(
            'srv',
            $this->parameter('ignored-slug', tools: ['get_car_info'])
        );

        $this->assertSame('srv', $server->getId());
        $this->assertSame('srv', $server->getUrlSlug());
        $this->assertSame(99, $server->getOwnerId());
        $this->assertSame(['get_car_info'], $server->getTools());
        $this->assertSame(99, $repository->get('srv')->access->owner);
    }

    public function testUpdateConfigurationThrowsNotFoundForUnknownId(): void
    {
        $service = $this->service($this->repository(), currentUserId: 42);

        $this->expectException(NotFoundException::class);

        $service->updateConfiguration('missing', $this->parameter('missing'));
    }

    public function testGetConfigurationBuildsUrlFromIssuer(): void
    {
        $repository = $this->repository(['srv' => $this->definition('srv', owner: 1)]);
        $service = $this->service($repository, currentUserId: 1);

        $server = $service->getConfiguration('srv');

        $this->assertSame('https://example.test/pimcore-mcp/studio/srv', $server->getUrl());
    }

    public function testListConfigurationsMapsAllServers(): void
    {
        $repository = $this->repository([
            'a' => $this->definition('a', owner: 1),
            'b' => $this->definition('b', owner: 1),
        ]);
        $service = $this->service($repository, currentUserId: 1);

        $servers = $service->listConfigurations();

        $this->assertCount(2, $servers);
        $this->assertSame(['a', 'b'], array_map(static fn ($s) => $s->getId(), $servers));
    }

    public function testDeleteConfigurationDelegatesToRepository(): void
    {
        $repository = $this->repository(['gone' => $this->definition('gone', owner: 1)]);
        $service = $this->service($repository, currentUserId: 1);

        $service->deleteConfiguration('gone');

        $this->assertFalse($repository->has('gone'));
    }

    private function service(
        McpServerConfigRepositoryInterface $repository,
        int $currentUserId,
    ): McpServerConfigurationService {
        return new McpServerConfigurationService(
            new McpServerHydrator(),
            $this->makeEmpty(EventDispatcherInterface::class),
            $repository,
            new McpToolRegistry([
                $this->tool('get_car_info', readOnly: true),
                $this->tool('delete_object', readOnly: false),
            ]),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['getId' => $currentUserId]),
            ]),
            self::ISSUER,
        );
    }

    /**
     * In-memory {@see McpServerConfigRepositoryInterface} so the service orchestration is unit-testable.
     *
     * @param array<string, McpServerDefinition> $seed
     */
    private function repository(array $seed = []): McpServerConfigRepositoryInterface
    {
        return new class($seed) implements McpServerConfigRepositoryInterface {
            /** @param array<string, McpServerDefinition> $servers */
            public function __construct(private array $servers)
            {
            }

            public function list(): array
            {
                return array_values($this->servers);
            }

            public function has(string $id): bool
            {
                return isset($this->servers[$id]);
            }

            public function isWriteable(): bool
            {
                return true;
            }

            public function get(string $id): McpServerDefinition
            {
                return $this->servers[$id] ?? throw new NotFoundException('MCP server', $id);
            }

            public function save(McpServerDefinition $server): void
            {
                $this->servers[$server->id] = $server;
            }

            public function delete(string $id): void
            {
                if (!isset($this->servers[$id])) {
                    throw new NotFoundException('MCP server', $id);
                }

                unset($this->servers[$id]);
            }
        };
    }

    /**
     * @param list<string> $tools
     */
    private function parameter(string $slug, array $tools = []): McpServerParameter
    {
        return new McpServerParameter(
            name: 'Server ' . $slug,
            urlSlug: $slug,
            description: 'A server',
            tools: $tools,
        );
    }

    private function definition(string $id, int $owner): McpServerDefinition
    {
        return new McpServerDefinition(
            id: $id,
            displayName: 'Server ' . $id,
            description: '',
            urlSlug: $id,
            toolIds: ['get_car_info'],
            scopes: ['mcp:read'],
            enabled: true,
            access: new McpServerAccess(owner: $owner),
        );
    }

    private function tool(string $name, bool $readOnly): McpToolInterface
    {
        return $this->makeEmpty(McpToolInterface::class, [
            'getDefinition' => new McpToolDefinition(
                name: $name,
                title: $name,
                description: $name,
                annotations: new McpToolAnnotations(readOnly: $readOnly),
            ),
        ]);
    }
}
