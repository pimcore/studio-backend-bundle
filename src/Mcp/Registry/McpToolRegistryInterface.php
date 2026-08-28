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

use Psr\Container\ContainerInterface;

/**
 * Catalogue of MCP tools contributed by services tagged {@see McpToolRegistry::TAG}
 * (SDK-native `#[McpTool]` services). Collected at compile time by
 * {@see \Pimcore\Bundle\StudioBackendBundle\DependencyInjection\CompilerPass\McpToolPass}.
 *
 * @internal
 */
interface McpToolRegistryInterface
{
    /**
     * @return list<McpToolReference>
     */
    public function all(): array;

    public function has(string $name): bool;

    public function get(string $name): ?McpToolReference;

    /**
     * @return list<string>
     */
    public function names(): array;

    /**
     * PSR container that resolves a tool's backing service by class, for the SDK
     * server to invoke `[class, method]` handlers.
     */
    public function getLocator(): ContainerInterface;
}
