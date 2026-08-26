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
 * The requesting user's resolved access to a server, so the UI can decide what to
 * render. {@see $write} implies {@see $read}. Distinct from the server's
 * `writeable` flag, which is about whether the storage target itself is editable.
 *
 * @internal
 */
#[Schema(
    schema: 'McpServerUserPermissions',
    title: 'MCP Server User Permissions',
    required: ['read', 'write'],
    type: 'object'
)]
final readonly class McpServerUserPermissions
{
    public function __construct(
        #[Property(description: 'The current user may view the server and copy its URL', type: 'boolean', example: true)]
        private bool $read,
        #[Property(description: 'The current user may edit, re-share or delete the server', type: 'boolean', example: false)]
        private bool $write,
    ) {
    }

    public function isRead(): bool
    {
        return $this->read;
    }

    public function isWrite(): bool
    {
        return $this->write;
    }
}
