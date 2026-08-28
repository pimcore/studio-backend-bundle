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

use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;

/**
 * Minimal built-in MCP tool: a liveness check that echoes "pong". Doubles as the
 * reference example of an SDK-native tool — a plain service with an `#[McpTool]`
 * method returning a {@see CallToolResult}, tagged for the studio tool registry.
 *
 * @internal
 */
final class PingTool
{
    #[McpTool(
        name: 'ping',
        title: 'Ping',
        description: 'Liveness check that returns "pong".',
        annotations: new ToolAnnotations(
            readOnlyHint: true,
            destructiveHint: false,
            idempotentHint: true,
            openWorldHint: false,
        ),
    )]
    public function execute(): CallToolResult
    {
        return new CallToolResult(
            content: [new TextContent('pong')],
            isError: false,
        );
    }
}
