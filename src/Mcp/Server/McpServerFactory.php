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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Server;

use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ServerCapabilities;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server;
use Mcp\Server\Builder;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\Psr16SessionStore;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Assembles a tools-only MCP {@see Server} for a configured server, one instance
 * per definition (cached). Each assigned tool is resolved from the registry and
 * bridged onto the SDK: the native {@see McpToolInterface::execute()} is wrapped
 * in a handler that reads the call arguments and maps the result back to the SDK
 * result type, so tools stay SDK-agnostic while the server speaks the protocol.
 *
 * @internal
 */
final class McpServerFactory implements McpServerFactoryInterface
{
    private const string SERVER_VERSION = '1.0.0';

    private const int SESSION_TTL = 86400;

    /**
     * @var array<string, Server>
     */
    private array $servers = [];

    public function __construct(
        private readonly McpToolRegistryInterface $toolRegistry,
        private readonly CacheInterface $sessionCache,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function createServer(McpServerDefinition $server): Server
    {
        if (isset($this->servers[$server->id])) {
            return $this->servers[$server->id];
        }

        $builder = Server::builder()
            ->setServerInfo('pimcore-' . $server->urlSlug, self::SERVER_VERSION, $server->description)
            ->setCapabilities(new ServerCapabilities(
                tools: true,
                resources: false,
                prompts: false,
                logging: false,
            ))
            ->setSession(
                sessionStore: new Psr16SessionStore(
                    $this->sessionCache,
                    'mcp_studio_' . $server->urlSlug . '_',
                    self::SESSION_TTL,
                )
            )
            ->setLogger($this->logger);

        foreach ($server->toolIds as $toolId) {
            $tool = $this->toolRegistry->get($toolId);
            if ($tool === null) {
                $this->logger->warning(
                    'MCP server "{server}" references unknown tool "{tool}"; skipping.',
                    ['server' => $server->id, 'tool' => $toolId]
                );

                continue;
            }

            $this->registerTool($builder, $tool);
        }

        return $this->servers[$server->id] = $builder->build();
    }

    private function registerTool(Builder $builder, McpToolInterface $tool): void
    {
        $definition = $tool->getDefinition();

        $builder->addTool(
            handler: static function (RequestContext $context) use ($tool): CallToolResult {
                $request = $context->getRequest();
                $arguments = $request instanceof CallToolRequest ? $request->arguments : [];
                $result = $tool->execute($arguments);

                return new CallToolResult(
                    content: [new TextContent($result->text)],
                    isError: $result->isError,
                );
            },
            name: $definition->name,
            title: $definition->title,
            description: $definition->description,
            annotations: ToolAnnotations::fromArray($definition->annotations->toArray()),
            inputSchema: $definition->inputSchema,
            outputSchema: $definition->outputSchema,
        );
    }
}
