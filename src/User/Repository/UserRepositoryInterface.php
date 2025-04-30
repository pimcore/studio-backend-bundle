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

namespace Pimcore\Bundle\StudioBackendBundle\User\Repository;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\User\Listing as UserListing;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface UserRepositoryInterface
{
    public function getUserListingByParentId(int $parentId): UserListing;

    /**
     * @throws NotFoundException
     */
    public function getUserById(int $userId): UserInterface;

    /**
     * @throws Exception
     */
    public function deleteUser(UserInterface $user): void;

    /**
     * @throws Exception
     */
    public function createUser(string $username, int $folderId): UserInterface;

    /**
     * @throws DatabaseException
     */
    public function updateUser(UserInterface $user): void;

    public function getUserListingByRoleId(int $roleId, ?int $excludeUserId = null): UserListing;

    /**
     * @return UserInterface[]
     *
     * @throws DatabaseException
     */
    public function getUsers(): array;

    /**
     * @return UserInterface[]
     *
     * @throws DatabaseException
     */
    public function searchUser(string $searchQuery): array;
}
