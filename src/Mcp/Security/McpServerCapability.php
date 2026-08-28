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
 * The three independent capabilities a user may hold on an MCP server:
 *
 *  - {@see View}   — see the server in the list and open its configuration read-only.
 *  - {@see Access} — connect an MCP client to the server over its URL (runtime use).
 *  - {@see Edit}   — change the configuration, its sharing, and delete it.
 *
 * They are orthogonal: unlike a read/write hierarchy, Edit does not imply Access.
 * Admins hold View and Edit on every server, but must be granted Access explicitly.
 *
 * @internal
 */
enum McpServerCapability: string
{
    case View = 'view';
    case Access = 'access';
    case Edit = 'edit';
}
