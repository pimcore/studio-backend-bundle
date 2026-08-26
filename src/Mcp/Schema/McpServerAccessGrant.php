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
 * One share entry as exposed by the API: a user or role id paired with the level
 * it is granted. Used in both the server response and the create/update payload.
 *
 * @internal
 */
#[Schema(
    schema: 'McpServerAccessGrant',
    title: 'MCP Server Access Grant',
    required: ['id', 'permission'],
    type: 'object'
)]
final readonly class McpServerAccessGrant
{
    public function __construct(
        #[Property(description: 'User or role id', type: 'integer', example: 42)]
        private int $id,
        #[Property(description: 'Granted access level', type: 'string', enum: ['read', 'write'], example: 'read')]
        private string $permission,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPermission(): string
    {
        return $this->permission;
    }
}
