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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ParentIdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UserWithPermissionParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ResetPassword;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\User as UserSchema;

/**
 * @internal
 */
interface UserServiceInterface
{
    /**
     * @throws RateLimitException|DomainConfigurationException|SendMailException
     */
    public function resetPassword(ResetPassword $resetPassword): void;

    public function getUserTreeListing(ParentIdParameter $userListParameter): Collection;

    /**
     * @throws NotFoundException|ForbiddenException|DatabaseException
     */
    public function deleteUser(int $userId): void;

    /**
     * @throws NotFoundException|DatabaseException
     */
    public function createUser(CreateParameter $createParameter): TreeNode;

    /**
     * @throws NotFoundException|DatabaseException
     */
    public function getUserById(int $userId): UserSchema;

    /**
     * @throws DatabaseException
     */
    public function getUsers(): Collection;

    /**
     * @throws DatabaseException
     */
    public function getUsersWithPermission(UserWithPermissionParameter $parameter): Collection;

    /**
     * @throws DatabaseException
     */
    public function userSearch(string $searchQuery): Collection;

    public function getUserNameById(int $userId): ?string;
}
