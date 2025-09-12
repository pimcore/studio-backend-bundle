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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\User\Role;
use Pimcore\Model\User\Role\Listing;
use Pimcore\Model\User\UserRoleInterface;

/**
 * @internal
 */
interface RoleRepositoryInterface
{
    /**
     * @return Role[]
     *
     * @throws DatabaseException
     */
    public function getRoles(): array;

    /**
     * @return Role[]
     *
     * @throws DatabaseException
     */
    public function getRolesWithPermission(string $permission): array;

    /**
     * @throws NotFoundException
     */
    public function getRoleById(int $roleId): Role;

    /**
     *
     * @throws DatabaseException
     */
    public function getRoleListingWithFolderByParentId(int $parentId): Listing;

    /**
     * @return Role[]
     *
     * @throws DatabaseException
     */
    public function searchRoles(string $searchQuery): array;

    /**
     * @throws Exception
     */
    public function deleteRole(Role $role): void;

    /**
     * @throws Exception
     */
    public function createRole(string $roleName, int $folderId): Role;

    /**
     * @throws DatabaseException
     */
    public function updateRole(UserRoleInterface $role): void;
}
