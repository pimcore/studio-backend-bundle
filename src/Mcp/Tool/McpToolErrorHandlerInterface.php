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

use Throwable;

/**
 * Shared error handling for the terminal generic `catch` of an MCP tool.
 *
 * Inject this into a tool and route its `catch (Throwable $e)` through {@see self::handle()}, then
 * wrap the returned string in whatever error envelope the tool already uses.
 */
interface McpToolErrorHandlerInterface
{
    /**
     * Logs $exception and returns the message that may be handed to the client.
     *
     * @param string               $toolName the public MCP tool name, e.g. "update_document"
     * @param array<string, mixed> $context  additional structured logging context, e.g. tool parameters
     */
    public function handle(Throwable $exception, string $toolName, array $context = []): string;
}
