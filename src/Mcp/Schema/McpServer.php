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

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'McpServer',
    title: 'MCP Server',
    required: [
        'id',
        'name',
        'urlSlug',
        'url',
        'tools',
        'scopes',
        'enabled',
        'shareGlobal',
        'sharedUsers',
        'sharedRoles',
        'writeable',
        'currentUserPermissions',
        'toolCount',
    ],
    type: 'object'
)]
final class McpServer implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    /**
     * @param list<string>                 $tools
     * @param list<string>                 $scopes
     * @param list<McpServerAccessGrant>   $sharedUsers
     * @param list<McpServerAccessGrant>   $sharedRoles
     */
    public function __construct(
        #[Property(description: 'Server id (also the url slug)', type: 'string', example: 'product-read')]
        private readonly string $id,
        #[Property(description: 'Display name', type: 'string', example: 'Product (read-only)')]
        private readonly string $name,
        #[Property(description: 'Description', type: 'string', example: 'Read access to product data', nullable: true)]
        private readonly ?string $description,
        #[Property(description: 'URL segment under /pimcore-mcp/studio/', type: 'string', example: 'product-read')]
        private readonly string $urlSlug,
        #[Property(description: 'Endpoint an MCP client connects to', type: 'string', example: 'https://host/pimcore-mcp/studio/product-read')]
        private readonly string $url,
        #[Property(description: 'Assigned tool ids', type: 'array', items: new Items(type: 'string'), example: ['get_car_info'])]
        private readonly array $tools,
        #[Property(description: 'Advertised OAuth scopes (derived from the tools)', type: 'array', items: new Items(type: 'string'), example: ['mcp:read'])]
        private readonly array $scopes,
        #[Property(description: 'Whether the server is enabled', type: 'boolean', example: true)]
        private readonly bool $enabled,
        #[Property(description: 'Owner user name. Null when the owner has been deleted.', type: 'string', example: 'john.doe', nullable: true)]
        private readonly ?string $owner,
        #[Property(description: 'Public: any authenticated user may view and use it (not edit)', type: 'boolean', example: false)]
        private readonly bool $shareGlobal,
        #[Property(description: 'Users shared with, each at a read/write level', type: 'array', items: new Items(ref: McpServerAccessGrant::class))]
        private readonly array $sharedUsers,
        #[Property(description: 'Roles shared with, each at a read/write level', type: 'array', items: new Items(ref: McpServerAccessGrant::class))]
        private readonly array $sharedRoles,
        #[Property(description: 'Whether the storage target allows editing at all', type: 'boolean', example: true)]
        private readonly bool $writeable,
        #[Property(description: 'The requesting user\'s resolved access to this server', ref: McpServerUserPermissions::class)]
        private readonly McpServerUserPermissions $currentUserPermissions,
        #[Property(description: 'Number of assigned tools', type: 'integer', example: 1)]
        private readonly int $toolCount,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getUrlSlug(): string
    {
        return $this->urlSlug;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return list<string>
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    /**
     * @return list<string>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }

    public function isShareGlobal(): bool
    {
        return $this->shareGlobal;
    }

    /**
     * @return list<McpServerAccessGrant>
     */
    public function getSharedUsers(): array
    {
        return $this->sharedUsers;
    }

    /**
     * @return list<McpServerAccessGrant>
     */
    public function getSharedRoles(): array
    {
        return $this->sharedRoles;
    }

    public function isWriteable(): bool
    {
        return $this->writeable;
    }

    public function getCurrentUserPermissions(): McpServerUserPermissions
    {
        return $this->currentUserPermissions;
    }

    public function getToolCount(): int
    {
        return $this->toolCount;
    }
}
