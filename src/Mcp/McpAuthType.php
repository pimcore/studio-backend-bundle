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
 * How an MCP request was authenticated.
 *
 * @internal
 */
enum McpAuthType: string
{
    /** An OAuth JWT access token. */
    case OAuth = 'oauth';

    /** A dynamically issued `pmcp_` bearer token. */
    case McpToken = 'mcp_token';

    /** A personal access token. */
    case Pat = 'pat';

    /** A Pimcore admin session forwarded by the browser. */
    case Session = 'session';
}
