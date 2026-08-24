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

use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolInterface;

/**
 * The catalogue of tools that self-registered via the MCP tool tag — the set an
 * MCP server may draw from. Keyed by the tool's definition name.
 *
 * @internal
 */
interface McpToolRegistryInterface
{
    /**
     * @return list<McpToolInterface>
     */
    public function all(): array;

    public function has(string $name): bool;

    public function get(string $name): ?McpToolInterface;

    /**
     * @return list<string>
     */
    public function names(): array;
}
