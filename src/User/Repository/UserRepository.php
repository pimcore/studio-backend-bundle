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
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
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
        private SecurityServiceInterface $securityService,
        private UserResolverInterface $userResolver,
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
        $roleCondition = '(roles = :roleId ' .
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
    public function getUsersWithPermission(string $permission, bool $includeCurrentUser): array
    {
        $users = $this->getUsers($includeCurrentUser);
        $usersWithPermission = [];
        foreach ($users as $user) {
            if ($user->isAllowed($permission)) {
                $usersWithPermission[] = $user;
            }
        }

        return $usersWithPermission;
    }

    /**
     * @return UserInterface[]
     *
     * @throws DatabaseException
     */
    public function getUsers(bool $includeCurrentUser = true): array
    {
        try {
            $userListing = new UserListing();
            $userListing->setCondition('`type` = :type', ['type' => 'user']);
            if (!$includeCurrentUser) {
                $userListing->addConditionParam(
                    'id != :currentUser',
                    ['currentUser' => $this->securityService->getCurrentUser()->getId()]
                );
            }
            $userListing->load();

            return $userListing->getUsers();
        } catch (Exception $e) {
            throw new  DatabaseException(sprintf('Error while fetching users: %s', $e->getMessage()));
        }
    }

    /**
     * @return UserInterface[]
     *
     * @throws DatabaseException
     */
    public function searchUser(string $searchQuery): array
    {
        $list = [];
        $q = '%' . $searchQuery . '%';

        try {
            $userListing = new UserListing();
            $userListing->setCondition(
                'name LIKE ? OR firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR id = ?',
                [$q, $q, $q, $q, (int)$searchQuery]
            );
            $userListing->setOrder('ASC');
            $userListing->setOrderKey('name');
            $userListing->load();

            foreach ($userListing->getUsers() as $user) {
                if ($user instanceof UserInterface && $user->getName() !== 'system') {
                    $list[] = $user;
                }
            }

            return $list;
        } catch (Exception $e) {
            throw new  DatabaseException(sprintf('Error while searching for users: %s', $e->getMessage()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersByNames(array $names): array
    {
        if (empty($names)) {
            return [];
        }

        $listing = new UserListing();
        $placeholders = implode(',', array_fill(0, count($names), '?'));
        $listing->setCondition('name IN (' . $placeholders . ')', $names);
        $listing->load();

        return $listing->getUsers();
    }
}
