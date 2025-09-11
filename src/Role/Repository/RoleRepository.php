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

namespace Pimcore\Bundle\StudioBackendBundle\Role\Repository;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\Role\RoleResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\User\Role;
use Pimcore\Model\User\Role\Listing;
use Pimcore\Model\User\UserRoleInterface;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(
        private RoleResolverInterface $roleResolver
    ) {
    }

    /**
     * @return Role[]
     *
     * @throws DatabaseException
     */
    public function getRoles(): array
    {
        try {
            $roleListing = new Listing();
            $roleListing->setCondition('`type` = ?', ['role']);
            $roleListing->load();

            return $roleListing->getRoles();
        } catch (Exception $e) {
            throw new  DatabaseException(sprintf('Error while fetching roles: %s', $e->getMessage()));
        }
    }

    /**
     * @return Role[]
     *
     * @throws DatabaseException
     */
    public function getRolesWithPermission(string $permission): array
    {
        $roles = $this->getRoles();
        $rolesWithPermission = [];
        foreach ($roles as $role) {
            if (in_array($permission, $role->getPermissions(), true)) {
                $rolesWithPermission[] = $role;
            }
        }

        return $rolesWithPermission;
    }

    /**
     * @throws NotFoundException
     */
    public function getRoleById(int $roleId): Role
    {
        $role = $this->roleResolver->getById($roleId);
        if (!$role instanceof Role) {
            throw new NotFoundException('Role', $roleId);
        }

        return $role;
    }

    /**
     *
     * @throws DatabaseException
     */
    public function getRoleListingWithFolderByParentId(int $parentId): Listing
    {
        try {
            $roleListing = new Listing();
            $roleListing->setCondition('parentId = ?', $parentId);
            $roleListing->setOrder('ASC');
            $roleListing->setOrderKey('name');
            $roleListing->load();

            return $roleListing;
        } catch (Exception $e) {
            throw new  DatabaseException(sprintf('Error while fetching roles: %s', $e->getMessage()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function searchRoles(string $searchQuery): array
    {
        $q = '%' . $searchQuery . '%';

        try {
            $roleListing = new Listing();
            $roleListing->setCondition(
                'name LIKE ? OR id = ?',
                [$q, (int)$searchQuery]
            );
            $roleListing->addConditionParam('`type` = ?', ['role']);
            $roleListing->setOrder('ASC');
            $roleListing->setOrderKey('name');
            $roleListing->load();

            return $roleListing->getRoles();
        } catch (Exception $e) {
            throw new DatabaseException(sprintf('Error while searching for roles: %s', $e->getMessage()));
        }
    }

    /**
     * @throws Exception
     */
    public function deleteRole(Role $role): void
    {
        $role->delete();
    }

    public function createRole(string $roleName, int $folderId): Role
    {
        return $this->roleResolver->create([
            'parentId' => $folderId,
            'name' => $roleName,
        ]);
    }

    /**
     * @throws DatabaseException
     */
    public function updateRole(UserRoleInterface $role): void
    {
        try {
            $role->save();
        } catch (Exception $exception) {
            throw new DatabaseException(
                sprintf(
                    'Error updating role with id %d: %s',
                    $role->getId(),
                    $exception->getMessage()
                )
            );
        }
    }
}
