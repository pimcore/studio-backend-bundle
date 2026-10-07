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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Server;

use Codeception\Test\Unit;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolReference;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Server\McpServerFactory;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\Builtin\PingTool;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class McpServerFactoryTest extends Unit
{
    public function testBuildsAServerFromAssignedTools(): void
    {
        $server = $this->factory()->createServer($this->definition(['ping']));

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testCachesTheServerPerDefinitionId(): void
    {
        $factory = $this->factory();
        $definition = $this->definition(['ping']);

        $this->assertSame($factory->createServer($definition), $factory->createServer($definition));
    }

    public function testUnknownToolIsSkippedNotFatal(): void
    {
        // 'nope' resolves to null in the registry; the server still builds.
        $server = $this->factory()->createServer($this->definition(['ping', 'nope']));

        $this->assertInstanceOf(Server::class, $server);
    }

    private function factory(): McpServerFactory
    {
        $registry = $this->createMock(McpToolRegistryInterface::class);
        $registry->method('get')->willReturnCallback(
            static fn (string $name): ?McpToolReference => $name === 'ping'
                ? new McpToolReference(
                    name: 'ping',
                    title: 'Ping',
                    description: 'Liveness check.',
                    annotations: new ToolAnnotations(readOnlyHint: true),
                    outputSchema: null,
                    className: PingTool::class,
                    method: 'execute',
                )
                : null
        );
        $registry->method('getLocator')->willReturn(new ServiceLocator([
            PingTool::class => static fn (): PingTool => new PingTool(),
        ]));

        return new McpServerFactory($registry, new Psr16Cache(new ArrayAdapter()), new NullLogger());
    }

    /**
     * @param list<string> $toolIds
     */
    private function definition(array $toolIds): McpServerDefinition
    {
        return new McpServerDefinition(
            id: 'demo',
            displayName: 'Demo',
            description: 'Demo server',
            urlSlug: 'demo',
            toolIds: $toolIds,
            scopes: ['mcp:read'],
            enabled: true,
            access: new McpServerAccess(shareGlobal: true),
        );
    }
}
