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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserDataObjectWorkspace;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserDocumentWorkspace;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserWorkspace;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * A service user: a machine identity authorized like a user, which never logs in
 * interactively, is never an administrator and has no usable password. The client secret is never
 * part of this schema.
 *
 * @internal
 */
#[Schema(
    schema: 'ServiceUser',
    title: 'Service User',
    description: 'A machine identity with its own permissions, roles and workspaces',
    required: [
        'id', 'name', 'email', 'active', 'permissions', 'roles', 'classes', 'docTypes',
        'assetWorkspaces', 'dataObjectWorkspaces', 'documentWorkspaces', 'clientId', 'lastLogin',
        'clientCredentialsEnabled', 'mcpTokens', 'responsibleUsers', 'responsibleRoles',
    ],
    type: 'object'
)]
final class ServiceUser implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    /**
     * @param string[] $permissions
     * @param int[] $roles
     * @param int[] $responsibleUsers
     * @param int[] $responsibleRoles
     * @param string[] $classes
     * @param string[] $docTypes
     * @param UserWorkspace[] $assetWorkspaces
     * @param UserDataObjectWorkspace[] $dataObjectWorkspaces
     * @param UserDocumentWorkspace[] $documentWorkspaces
     */
    public function __construct(
        #[Property(description: 'ID of the service user', type: 'integer', example: 42)]
        private readonly int $id,
        #[Property(description: 'Unique name, shared with human users', type: 'string', example: 'svc-erp-sync')]
        private readonly string $name,
        #[Property(
            description: 'Contact email of the team responsible',
            type: 'string',
            example: 'erp-team@example.com',
            nullable: true
        )]
        private readonly ?string $email,
        #[Property(description: 'Whether the service user may authenticate', type: 'boolean', example: true)]
        private readonly bool $active,
        #[Property(description: 'Permissions', type: 'array', items: new Items(type: 'string'), example: ['assets'])]
        private readonly array $permissions,
        #[Property(description: 'IDs of the assigned roles', type: 'array', items: new Items(type: 'integer'))]
        private readonly array $roles,
        #[Property(description: 'Allowed classes', type: 'array', items: new Items(type: 'string'))]
        private readonly array $classes,
        #[Property(description: 'Allowed document types', type: 'array', items: new Items(type: 'string'))]
        private readonly array $docTypes,
        #[Property(description: 'Asset workspaces', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private readonly array $assetWorkspaces,
        #[Property(
            description: 'Data object workspaces',
            type: 'array',
            items: new Items(ref: UserDataObjectWorkspace::class)
        )]
        private readonly array $dataObjectWorkspaces,
        #[Property(
            description: 'Document workspaces',
            type: 'array',
            items: new Items(ref: UserDocumentWorkspace::class)
        )]
        private readonly array $documentWorkspaces,
        #[Property(
            description: 'ID of the bound OAuth client (client_credentials)',
            type: 'string',
            example: 'svc_4f1c2b',
            nullable: true
        )]
        private readonly ?string $clientId,
        #[Property(description: 'Timestamp of the last login', type: 'integer', example: 1718757677, nullable: true)]
        private readonly ?int $lastLogin,
        #[Property(
            description: 'Whether the OAuth client_credentials grant is enabled on this instance. '
                . 'While false, the client secret authenticates nothing; use an MCP access token.',
            type: 'boolean',
            example: false
        )]
        private readonly bool $clientCredentialsEnabled,
        #[Property(
            description: 'Summary of the MCP access tokens of the service user (never the tokens themselves)',
            ref: ServiceUserMcpTokens::class,
            type: 'object'
        )]
        private readonly ServiceUserMcpTokens $mcpTokens,
        #[Property(
            description: 'IDs of the users who answer for the service user',
            type: 'array',
            items: new Items(type: 'integer')
        )]
        private readonly array $responsibleUsers,
        #[Property(
            description: 'IDs of the roles whose members answer for the service user',
            type: 'array',
            items: new Items(type: 'integer')
        )]
        private readonly array $responsibleRoles,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return string[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return int[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return string[]
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * @return string[]
     */
    public function getDocTypes(): array
    {
        return $this->docTypes;
    }

    /**
     * @return UserWorkspace[]
     */
    public function getAssetWorkspaces(): array
    {
        return $this->assetWorkspaces;
    }

    /**
     * @return UserDataObjectWorkspace[]
     */
    public function getDataObjectWorkspaces(): array
    {
        return $this->dataObjectWorkspaces;
    }

    /**
     * @return UserDocumentWorkspace[]
     */
    public function getDocumentWorkspaces(): array
    {
        return $this->documentWorkspaces;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function getLastLogin(): ?int
    {
        return $this->lastLogin;
    }

    public function isClientCredentialsEnabled(): bool
    {
        return $this->clientCredentialsEnabled;
    }

    public function getMcpTokens(): ServiceUserMcpTokens
    {
        return $this->mcpTokens;
    }

    /**
     * @return int[]
     */
    public function getResponsibleUsers(): array
    {
        return $this->responsibleUsers;
    }

    /**
     * @return int[]
     */
    public function getResponsibleRoles(): array
    {
        return $this->responsibleRoles;
    }
}
