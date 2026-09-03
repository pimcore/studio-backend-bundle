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
 * One share entry as exposed by the API: a user or role name with three independent
 * capabilities — read (see the server + config), access (connect a client), and edit
 * (change the config). Edit implies read. Used in both the server response and the
 * create/update payload.
 *
 * @internal
 */
#[Schema(
    schema: 'McpServerAccessGrant',
    title: 'MCP Server Access Grant',
    required: ['name', 'canRead', 'canAccess', 'canEdit'],
    type: 'object'
)]
final readonly class McpServerAccessGrant
{
    public function __construct(
        #[Property(description: 'User or role name', type: 'string', example: 'john.doe')]
        private string $name,
        #[Property(description: 'May see the server and its configuration (implied by canEdit)', type: 'boolean', example: true)]
        private bool $canRead,
        #[Property(description: 'May connect a client to the server at runtime', type: 'boolean', example: true)]
        private bool $canAccess,
        #[Property(description: 'May edit the server configuration', type: 'boolean', example: false)]
        private bool $canEdit,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isCanRead(): bool
    {
        return $this->canRead;
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
