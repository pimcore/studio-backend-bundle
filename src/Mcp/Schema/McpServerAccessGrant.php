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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * One share entry as exposed by the API: a user or role name paired with the level
 * it is granted. Used in both the server response and the create/update payload.
 * Names (not ids) so a configuration is portable across instances.
 *
 * @internal
 */
#[Schema(
    schema: 'McpServerAccessGrant',
    title: 'MCP Server Access Grant',
    required: ['name', 'permission'],
    type: 'object'
)]
final readonly class McpServerAccessGrant
{
    public function __construct(
        #[Property(description: 'User or role name', type: 'string', example: 'john.doe')]
        private string $name,
        #[Property(description: 'Granted access level', type: 'string', enum: ['read', 'write'], example: 'read')]
        private string $permission,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPermission(): string
    {
        return $this->permission;
    }
}
