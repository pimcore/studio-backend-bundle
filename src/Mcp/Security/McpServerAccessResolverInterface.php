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
 * Resolves the three independent capabilities ({@see McpServerCapability}) a user
 * holds on an MCP server. Used by the Studio API (view/edit) and the runtime
 * serving endpoint (access).
 *
 * @internal
 */
interface McpServerAccessResolverInterface
{
    public function isAllowed(
        McpServerDefinition $server,
        McpServerCapability $capability,
        UserInterface $user
    ): bool;

    /**
     * All three capabilities resolved at once, for building the response DTO.
     *
     * @return array{view: bool, access: bool, edit: bool}
     */
    public function resolve(McpServerDefinition $server, UserInterface $user): array;
}
