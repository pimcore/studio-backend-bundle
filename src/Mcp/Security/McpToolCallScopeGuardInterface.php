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

use Pimcore\Bundle\StudioBackendBundle\Mcp\Exception\InsufficientMcpScopeException;

/**
 * Keeps an OAuth access token from calling a tool its scopes do not cover.
 *
 * @internal
 */
interface McpToolCallScopeGuardInterface
{
    /**
     * Checks the JSON-RPC message(s) of an MCP request body. Only a token that
     * carries scopes is limited: with scopes given and `mcp:write` not among them, a
     * `tools/call` of a tool that is not annotated read-only is refused. No scopes
     * (credentials without scopes, tokens issued without one) means no limit.
     *
     * @param list<string>|null $grantedScopes null when the credential carries no scopes
     *
     * @throws InsufficientMcpScopeException
     */
    public function assertAllowed(string $requestBody, ?array $grantedScopes): void;
}
