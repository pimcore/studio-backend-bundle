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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp;

/**
 * Marker interface for MCP tool classes.
 * Enables type-safe collection by the McpToolRegistryPass compiler pass.
 *
 * All tool classes must:
 * - Implement this interface
 * - Be tagged with `pimcore.mcp_tool` (group attribute required)
 * - Have a method annotated with #[McpTool] that returns CallToolResult
 */
interface McpToolInterface
{
}
