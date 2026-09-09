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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Scope;

use Pimcore\Bundle\StudioBackendBundle\Mcp\McpScopes;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeProviderInterface;

/**
 * The scopes this bundle's own MCP servers use.
 *
 * @internal
 */
final class McpScopeProvider implements ScopeProviderInterface
{
    public function scopes(): array
    {
        return [McpScopes::READ, McpScopes::WRITE];
    }
}
