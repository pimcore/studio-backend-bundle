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

use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Model\UserInterface;

/**
 * Decides whether a user may use a given MCP server, following the bundle's
 * sharing model (admin bypass, then global / owner / shared users / shared roles).
 *
 * @internal
 */
interface McpServerAccessResolverInterface
{
    public function isAllowed(McpServerDefinition $server, UserInterface $user): bool;
}
