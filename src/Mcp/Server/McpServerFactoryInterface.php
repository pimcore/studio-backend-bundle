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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Server;

use Mcp\Server;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;

/**
 * Builds a runnable MCP {@see Server} for a configured server definition.
 *
 * @internal
 */
interface McpServerFactoryInterface
{
    public function createServer(McpServerDefinition $server): Server;
}
