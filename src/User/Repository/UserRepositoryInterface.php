<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
}
