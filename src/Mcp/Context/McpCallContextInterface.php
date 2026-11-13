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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Context;

/**
 * Marks that the current code runs inside an MCP tool call. Both MCP servers (the configured servers of this
 * bundle and the Agent bundle's chat endpoints) wrap each tool call in {@see self::run()}.
 *
 * Inside an MCP call a coauthor claimed in a save payload is never honoured: the coauthor comes only from the
 * server-side coauthor context, or there is none.
 */
interface McpCallContextInterface
{
    /**
     * True while a callback passed to {@see self::run()} is executing.
     */
    public function isInMcpCall(): bool;

    /**
     * Runs $callback marked as an MCP tool call; the marker is removed afterwards, also when $callback throws.
     *
     * @template T
     *
     * @param-immediately-invoked-callable $callback
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public function run(callable $callback): mixed;
}
