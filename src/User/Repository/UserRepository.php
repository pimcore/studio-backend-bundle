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
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\User;
use Pimcore\Model\User\Listing as UserListing;
use Pimcore\Model\UserInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private UserResolverInterface $userResolver
    ) {
    }

    public function getUserListingByParentId(int $parentId): UserListing
    {
        $listing = new UserListing();
        $listing->setCondition('parentId = ?', $parentId);
        $listing->setOrder('ASC');
        $listing->setOrderKey('name');
        $listing->load();

        return $listing;
    }

    /**
     * @throws NotFoundException
     */
    public function getUserById(int $userId): UserInterface
    {
        $user = $this->userResolver->getById($userId);

        if (!$user instanceof User) {
            throw new NotFoundException('User', $userId);
        }

        return $user;
    }

    /**
     * @throws Exception
     */
    public function deleteUser(UserInterface $user): void
    {
        $user->delete();
    }

    /**
     * @throws Exception
     */
    public function createUser(string $username, int $folderId): UserInterface
    {
        return $this->userResolver->create([
            'parentId' => $folderId,
            'name' => $username,
            'password' => '',
            'active' => true,
        ]);
    }

    public function updateUser(UserInterface $user): void
    {
        try {
            $user->save();
        } catch (Exception $exception) {
            throw new DatabaseException(
                sprintf(
                    'Error updating user with id %d: %s',
                    $user->getId(),
                    $exception->getMessage()
                )
            );
        }
    }

    public function getUserListingByRoleId(int $roleId, ?int $excludeUserId = null): UserListing
    {
        $listing = new UserListing();
        if ($excludeUserId !== null) {
            $listing->addConditionParam('id != :excludeUser', ['excludeUser' => $excludeUserId]);
        }
        $roleCondition = '(roles = :roleId' .
            'OR roles LIKE :roleIdEnds OR roles LIKE :roleIdStarts OR roles LIKE :roleIdContains)';
        $roleParams = [
            'roleId' => $roleId,
            'roleIdEnds' => '%,' . $roleId,
            'roleIdStarts' => $roleId . ',%',
            'roleIdContains' => '%,' . $roleId . ',%',
        ];
        $listing->addConditionParam('active = :active', ['active' => 1]);
        $listing->addConditionParam($roleCondition, $roleParams);
        $listing->setOrder('ASC');
        $listing->setOrderKey('name');
        $listing->load();

        return $listing;
    }

    /**
     * @return UserInterface[]
     *
     * @throws DatabaseException
     */
    public function getUsers(): array
    {
        try {
            $userListing = new UserListing();
            $userListing->setCondition('`type` = ?', ['user']);
            $userListing->load();

            return $userListing->getUsers();
        } catch (Exception $e) {
            throw new  DatabaseException(sprintf('Error while fetching users: %s', $e->getMessage()));
        }
    }
}
