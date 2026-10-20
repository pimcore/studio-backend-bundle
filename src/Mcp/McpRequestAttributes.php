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
 * Names of the request attributes the MCP firewall authenticators set once a request is
 * authenticated, so that tools and listeners can tell how the caller proved its identity.
 *
 * @internal
 */
interface McpRequestAttributes
{
    /**
     * Holds a {@see McpAuthType}.
     */
    public const string AUTH_TYPE = '_mcp_auth_type';

    /**
     * Holds the list<string> of scopes the validated OAuth access token carries. Only set
     * when {@see AUTH_TYPE} is {@see McpAuthType::OAuth}.
     */
    public const string OAUTH_SCOPES = '_mcp_oauth_scopes';
}
