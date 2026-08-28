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

use Mcp\Capability\Discovery\DocBlockParser;
use Mcp\Capability\Discovery\SchemaGenerator;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server;
use Mcp\Server\Builder;
use Mcp\Server\Session\Psr16SessionStore;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolReference;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\ToolInputSchemaNormalizer;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use ReflectionMethod;

/**
 * Assembles a tools-only MCP {@see Server} for a configured server, one instance
 * per definition (cached). Assigned tools are SDK-native `#[McpTool]` services;
 * each is registered straight onto the SDK builder via {@see Builder::addTool()}
 * with `[class, method]`, and the registry's locator resolves the backing service
 * at call time — no bundle-specific tool bridge.
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
            ->setContainer($this->toolRegistry->getLocator())
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

    private function registerTool(Builder $builder, McpToolReference $tool): void
    {
        $builder->addTool(
            handler: [$tool->className, $tool->method],
            name: $tool->name,
            title: $tool->title,
            description: $tool->description,
            annotations: $tool->annotations,
            inputSchema: $this->buildInputSchema($tool->className, $tool->method),
            outputSchema: $tool->outputSchema,
        );
    }

    /**
     * The SDK can derive the input schema from `#[Schema]` parameter attributes, but
     * it is generated here so {@see ToolInputSchemaNormalizer} can repair the
     * generated parameter types before registration.
     *
     * @param class-string $class
     *
     * @return array<string, mixed>
     */
    private function buildInputSchema(string $class, string $method): array
    {
        $generator = new SchemaGenerator(new DocBlockParser());

        return ToolInputSchemaNormalizer::normalize($generator->generate(new ReflectionMethod($class, $method)));
    }
}
