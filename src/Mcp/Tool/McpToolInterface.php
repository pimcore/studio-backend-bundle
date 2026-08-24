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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Tool;

/**
 * A Pimcore MCP tool. A service implementing this interface self-registers (it is
 * auto-tagged {@see \Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistry::TAG})
 * and becomes available to assign to an MCP server. The tool describes itself via
 * {@see getDefinition()} — including the annotations that determine its required
 * OAuth scope — and runs via {@see execute()}.
 *
 * @internal
 */
interface McpToolInterface
{
    public function getDefinition(): McpToolDefinition;

    /**
     * @param array<string, mixed> $arguments validated against the definition's input schema
     */
    public function execute(array $arguments): McpToolResult;
}
