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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Security;

/**
 * The access level a user or role may hold on an MCP server, mirroring the agent
 * bundle's run/update pair:
 *
 *  - {@see Read}  — see the server in the list, view its configuration, copy its
 *                   URL, and connect a client to it at runtime.
 *  - {@see Write} — everything Read allows, plus edit the configuration, change
 *                   its sharing, and delete it.
 *
 * {@see Write} implies {@see Read} (never the reverse): you cannot edit what you
 * cannot open.
 *
 * @internal
 */
enum McpServerPermission: string
{
    case Read = 'read';
    case Write = 'write';

    /**
     * Whether holding this level grants the {@see $requested} level. Write grants
     * both; Read grants only Read.
     */
    public function grants(self $requested): bool
    {
        return $this === self::Write || $this === $requested;
    }
}
