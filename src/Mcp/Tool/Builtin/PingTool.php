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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\Builtin;

use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolAnnotations;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolResult;

/**
 * Built-in health-check tool: takes no arguments and answers "pong". Read-only,
 * so it requires only mcp:read — a minimal, dependency-free tool that lets a
 * server be exercised end-to-end without the agent bundle.
 *
 * @internal
 */
final class PingTool implements McpToolInterface
{
    public function getDefinition(): McpToolDefinition
    {
        return new McpToolDefinition(
            name: 'ping',
            title: 'Ping',
            description: 'Health-check tool that returns "pong".',
            annotations: new McpToolAnnotations(readOnly: true, idempotent: true),
            inputSchema: ['type' => 'object', 'properties' => [], 'additionalProperties' => false],
        );
    }

    public function execute(array $arguments): McpToolResult
    {
        return McpToolResult::text('pong');
    }
}
