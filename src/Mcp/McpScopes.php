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

use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistryInterface;
use function array_keys;

/**
 * OAuth scopes used to gate MCP access. A tool that only reads requires
 * {@see READ}; anything else requires {@see WRITE}.
 *
 * @internal
 */
final class McpScopes
{
    public const string READ = 'mcp:read';

    public const string WRITE = 'mcp:write';

    /**
     * The scope a tool requires, derived from its read-only annotation: a
     * read-only tool needs {@see READ}, anything else the fail-safe {@see WRITE}.
     */
    public static function forReadOnly(bool $readOnly): string
    {
        return $readOnly ? self::READ : self::WRITE;
    }

    /**
     * The scopes a managed MCP server supports: the ones its definition declares, or
     * else the ones its assigned tools require. One rule for the protected resource the
     * server registers and for what Studio shows, so the two cannot disagree. Tool ids
     * the registry does not know contribute nothing.
     *
     * @return list<string>
     */
    public static function forServer(McpServerDefinition $server, McpToolRegistryInterface $toolRegistry): array
    {
        if ($server->scopes !== []) {
            return $server->scopes;
        }

        $scopes = [];
        foreach ($server->toolIds as $toolId) {
            $tool = $toolRegistry->get($toolId);
            if ($tool !== null) {
                $scopes[self::forReadOnly($tool->isReadOnly())] = true;
            }
        }

        return array_keys($scopes);
    }
}
