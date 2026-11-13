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
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @internal
 */
#[Schema(
    schema: 'ServiceUserCreate',
    title: 'Create Service User',
    description: 'A new service user with its permissions, roles and workspaces',
    required: ['name'],
    type: 'object'
)]
final readonly class CreateParameter
{
    /**
     * @param string[] $permissions
     * @param int[] $roles
     * @param string[] $classes
     * @param string[] $docTypes
     * @param UserWorkspace[] $assetWorkspaces
     * @param UserDataObjectWorkspace[] $dataObjectWorkspaces
     * @param UserDocumentWorkspace[] $documentWorkspaces
     * @param list<string>|null $scopes
     */
    public function __construct(
        #[Property(
            description: 'Unique name across users and service users',
            type: 'string',
            example: 'svc-erp-sync'
        )]
        #[NotBlank]
        #[Length(max: 64)]
        #[Regex(
            pattern: '/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
            message: 'Use letters, digits, dots, underscores and hyphens, starting with a letter or digit.'
        )]
        private string $name,
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
            description: 'OAuth scopes the bound client may request; all known scopes when omitted',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['mcp:read'],
            nullable: true
        )]
        private ?array $scopes = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return list<string>|null
     */
    public function getScopes(): ?array
    {
        return $this->scopes;
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
}
