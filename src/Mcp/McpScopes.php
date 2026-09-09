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
}
