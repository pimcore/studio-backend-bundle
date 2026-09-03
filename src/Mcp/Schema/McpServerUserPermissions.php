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
 * The requesting user's resolved capabilities on a server, so the UI can decide
 * what to render. Distinct from the server's `writeable` flag, which is about
 * whether the storage target itself is editable.
 *
 * @internal
 */
#[Schema(
    schema: 'McpServerUserPermissions',
    title: 'MCP Server User Permissions',
    required: ['canView', 'canAccess', 'canEdit'],
    type: 'object'
)]
final readonly class McpServerUserPermissions
{
    public function __construct(
        #[Property(description: 'The current user may view the server and its config', type: 'boolean', example: true)]
        private bool $canView,
        #[Property(description: 'The current user may connect a client to the server', type: 'boolean', example: false)]
        private bool $canAccess,
        #[Property(description: 'The current user may edit, re-share or delete the server', type: 'boolean', example: false)]
        private bool $canEdit,
    ) {
    }

    public function isCanView(): bool
    {
        return $this->canView;
    }

    public function isCanAccess(): bool
    {
        return $this->canAccess;
    }

    public function isCanEdit(): bool
    {
        return $this->canEdit;
    }
}
