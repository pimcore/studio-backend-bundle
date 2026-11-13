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
use Mcp\Capability\Registry\ElementReference;
use Mcp\Capability\Registry\ReferenceHandlerInterface;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server;
use Mcp\Server\Handler\Request\CallToolHandler;
use Mcp\Server\Session\SessionInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Context\McpCallContext;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolReference;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Server\McpServerFactory;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\Builtin\PingTool;
use Pimcore\Model\Version\CoauthorContext;
use Psr\Log\NullLogger;
use ReflectionProperty;
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

    public function testEveryToolCallOfTheBuiltServerRunsWithTheServerSlugAsMcpServerCoauthor(): void
    {
        $coauthorContext = new CoauthorContext();
        $mcpCallContext = new McpCallContext();
        $tool = new RecordingCoauthorTool($coauthorContext, $mcpCallContext);

        $registry = $this->createMock(McpToolRegistryInterface::class);
        $registry->method('get')->willReturn(new McpToolReference(
            name: 'record',
            title: 'Record',
            description: 'Records the coauthor state of the call.',
            annotations: new ToolAnnotations(readOnlyHint: false),
            outputSchema: null,
            className: RecordingCoauthorTool::class,
            method: 'execute',
        ));
        $registry->method('getLocator')->willReturn(new ServiceLocator([
            RecordingCoauthorTool::class => static fn (): RecordingCoauthorTool => $tool,
        ]));
        $factory = new McpServerFactory(
            $registry,
            new Psr16Cache(new ArrayAdapter()),
            new NullLogger(),
            $coauthorContext,
            $mcpCallContext,
        );

        $referenceHandler = $this->referenceHandlerOf($factory->createServer($this->definition(['record'])));
        $referenceHandler->handle(
            new ElementReference([RecordingCoauthorTool::class, 'execute']),
            ['_session' => $this->createMock(SessionInterface::class)]
        );

        $this->assertSame(['mcp-server', 'demo', true], $tool->seen);
        $this->assertFalse($coauthorContext->isActive());
        $this->assertFalse($mcpCallContext->isInMcpCall());
    }

    /**
     * The SDK keeps the handler private; read it from the call-tool handler of the built server.
     */
    private function referenceHandlerOf(Server $server): ReferenceHandlerInterface
    {
        $protocol = (new ReflectionProperty($server, 'protocol'))->getValue($server);
        $handlers = (new ReflectionProperty($protocol, 'requestHandlers'))->getValue($protocol);

        foreach ($handlers as $handler) {
            if ($handler instanceof CallToolHandler) {
                return (new ReflectionProperty($handler, 'referenceHandler'))->getValue($handler);
            }
        }

        $this->fail('The built server has no call-tool handler.');
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

        return new McpServerFactory(
            $registry,
            new Psr16Cache(new ArrayAdapter()),
            new NullLogger(),
            new CoauthorContext(),
            new McpCallContext(),
        );
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
            toolIds: $toolIds,
            scopes: ['mcp:read'],
            enabled: true,
            access: new McpServerAccess(shareGlobal: true),
        );
    }
}

/**
 * A write tool as a test double: it records the coauthor state it runs in, and sets nothing itself.
 */
final class RecordingCoauthorTool
{
    /**
     * @var list<mixed>
     */
    public array $seen = [];

    public function __construct(
        private readonly CoauthorContext $coauthorContext,
        private readonly McpCallContext $mcpCallContext,
    ) {
    }

    public function execute(): string
    {
        $this->seen = [
            $this->coauthorContext->getType(),
            $this->coauthorContext->getCoauthor(),
            $this->mcpCallContext->isInMcpCall(),
        ];

        return 'done';
    }
}
