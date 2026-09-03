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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Registry;

use Mcp\Schema\ToolAnnotations;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use function array_keys;
use function array_values;
use function is_array;

/**
 * Name-keyed catalogue of the studio backend's MCP tools. Tools are SDK-native
 * `#[McpTool]` services tagged {@see TAG}; {@see McpToolPass} reflects the
 * attribute at compile time and injects the descriptor metadata plus a service
 * locator here (metadata is passed as plain arrays so it survives container
 * compilation, and SDK objects are rebuilt lazily).
 *
 * @internal
 *
 * @phpstan-type ToolMetadata array{
 *     class: class-string,
 *     method: string,
 *     title: string|null,
 *     description: string,
 *     annotations: array<string, mixed>|null,
 *     outputSchema: array<string, mixed>|null
 * }
 */
final class McpToolRegistry implements McpToolRegistryInterface
{
    public const string TAG = 'pimcore.studio_backend.mcp_tool';

    /**
     * @var array<string, McpToolReference>|null
     */
    private ?array $resolved = null;

    /**
     * @param array<string, ToolMetadata> $toolMetadata name-keyed tool descriptors
     */
    public function __construct(
        private readonly array $toolMetadata = [],
        private readonly ?ContainerInterface $toolLocator = null,
    ) {
    }

    public function all(): array
    {
        return array_values($this->references());
    }

    public function has(string $name): bool
    {
        return isset($this->references()[$name]);
    }

    public function get(string $name): ?McpToolReference
    {
        return $this->references()[$name] ?? null;
    }

    public function names(): array
    {
        return array_keys($this->references());
    }

    public function getLocator(): ContainerInterface
    {
        return $this->toolLocator ?? new ServiceLocator([]);
    }

    /**
     * @return array<string, McpToolReference>
     */
    private function references(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $references = [];
        foreach ($this->toolMetadata as $name => $meta) {
            $annotations = $meta['annotations'];
            $references[$name] = new McpToolReference(
                name: $name,
                title: $meta['title'],
                description: $meta['description'],
                annotations: is_array($annotations) ? ToolAnnotations::fromArray($annotations) : null,
                outputSchema: $meta['outputSchema'],
                className: $meta['class'],
                method: $meta['method'],
            );
        }

        return $this->resolved = $references;
    }
}
