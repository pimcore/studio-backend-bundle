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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServer;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServerUserPermissions;

/**
 * @internal
 */
interface McpServerHydratorInterface
{
    /**
     * @param list<string> $scopes
     */
    public function hydrate(
        McpServerDefinition $definition,
        string $url,
        array $scopes,
        bool $writeable,
        McpServerUserPermissions $currentUserPermissions
    ): McpServer;
}
