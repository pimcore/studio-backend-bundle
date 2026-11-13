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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserDataObjectWorkspace;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserDocumentWorkspace;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserWorkspace;
use Symfony\Component\Validator\Constraints\Email;

/**
 * The fields of a service user that may change. Name, password, admin rights, two-factor
 * settings and the active flag are not among them. The update endpoint maps this payload
 * with allow_extra_attributes false, so a request that sends any key not declared here is
 * refused rather than silently ignored. The name stays fixed because MCP server access is
 * matched by name.
 *
 * @internal
 */
#[Schema(
    schema: 'ServiceUserUpdate',
    title: 'Update Service User',
    description: 'Contact, permissions, roles and workspaces of a service user',
    type: 'object'
)]
final readonly class UpdateParameter
{
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
        #[Property(
            description: 'Contact email of the team responsible',
            type: 'string',
            example: 'erp-team@example.com',
            nullable: true
        )]
        #[Email]
        private ?string $email = null,
        #[Property(description: 'Permissions', type: 'array', items: new Items(type: 'string'), example: ['assets'])]
        private array $permissions = [],
        #[Property(description: 'IDs of the roles', type: 'array', items: new Items(type: 'integer'), example: [5])]
        private array $roles = [],
        #[Property(description: 'Allowed classes', type: 'array', items: new Items(type: 'string'))]
        private array $classes = [],
        #[Property(description: 'Allowed document types', type: 'array', items: new Items(type: 'string'))]
        private array $docTypes = [],
        #[Property(description: 'Asset workspaces', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private array $assetWorkspaces = [],
        #[Property(
            description: 'Data object workspaces',
            type: 'array',
            items: new Items(ref: UserDataObjectWorkspace::class)
        )]
        private array $dataObjectWorkspaces = [],
        #[Property(
            description: 'Document workspaces',
            type: 'array',
            items: new Items(ref: UserDocumentWorkspace::class)
        )]
        private array $documentWorkspaces = [],
        #[Property(
            description: 'IDs of the users who answer for the service user; service users are not allowed',
            type: 'array',
            items: new Items(type: 'integer'),
            example: [3]
        )]
        private array $responsibleUsers = [],
        #[Property(
            description: 'IDs of the roles whose members answer for the service user',
            type: 'array',
            items: new Items(type: 'integer'),
            example: [5]
        )]
        private array $responsibleRoles = [],
    ) {
    }

    public function getEmail(): ?string
    {
        return $this->email;
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
