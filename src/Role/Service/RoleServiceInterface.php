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

namespace Pimcore\Bundle\StudioBackendBundle\Role\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ParentIdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Role\MappedParameter\UpdateRoleParameter;
use Pimcore\Bundle\StudioBackendBundle\Role\Schema\DetailedRole;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter;

/**
 * @internal
 */
interface RoleServiceInterface
{
    /**
     * @throws DatabaseException
     */
    public function getRoles(): Collection;

    /**
     * @throws DatabaseException
     */
    public function getRoleTreeCollection(ParentIdParameter $listingParameter): Collection;

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function deleteRole(int $roleId): void;

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function createRole(CreateParameter $createParameter): TreeNode;

    /**
     * @throws NotFoundException
     */
    public function getRoleById(int $roleId): DetailedRole;

    /**
     * @throws NotFoundException|DatabaseException
     */
    public function updateRoleById(int $roleId, UpdateRoleParameter $updateRoleParameter): void;
}
