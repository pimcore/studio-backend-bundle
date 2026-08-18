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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

/**
 * @internal
 */
trait StudioBackendPathTrait
{
    /**
     * Kept in sync with PimcoreStudioBackendExtension::MCP_FIREWALL_PATTERN. The MCP firewall
     * serves a path space of its own, outside the Studio API url_prefix, so anything that
     * scopes itself to Studio traffic has to name it explicitly or MCP escapes.
     */
    private const string MCP_PATH_PREFIX = '/pimcore-mcp/';

    private function isStudioBackendPath(string $path, string $urlPrefix): bool
    {
        return str_starts_with($path, $urlPrefix);
    }

    private function isMcpPath(string $path): bool
    {
        return str_starts_with($path, self::MCP_PATH_PREFIX);
    }
}
