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
use Symfony\Component\Validator\Constraints\IsNull;
use function array_filter;
use function array_keys;

/**
 * The fields of a service user that may change. Name, password, admin rights and two-factor
 * settings are not among them: a payload that sends one of them is refused with a validation
 * error rather than silently ignored. The name stays fixed because MCP server access is
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
    private const string REFUSED = 'A service user has no such field; it cannot be set.';

    /**
     * @param string[] $permissions
     * @param int[] $roles
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
        #[IsNull(message: self::REFUSED)]
        private mixed $name = null,
        #[IsNull(message: self::REFUSED)]
        private mixed $password = null,
        #[IsNull(message: self::REFUSED)]
        private mixed $admin = null,
        #[IsNull(message: self::REFUSED)]
        private mixed $twoFactorAuthenticationRequired = null,
        #[IsNull(message: self::REFUSED)]
        private mixed $twoFactorAuthentication = null,
    ) {
    }

    /**
     * Names of the sent fields a service user does not have.
     *
     * @return list<string>
     */
    public function getRefusedFields(): array
    {
        $refused = [
            'name' => $this->name,
            'password' => $this->password,
            'admin' => $this->admin,
            'twoFactorAuthenticationRequired' => $this->twoFactorAuthenticationRequired,
            'twoFactorAuthentication' => $this->twoFactorAuthentication,
        ];

        return array_keys(array_filter($refused, static fn (mixed $value): bool => $value !== null));
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
}
