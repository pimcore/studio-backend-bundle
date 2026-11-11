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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Resolver\UserAccountResolverInterface;
use Pimcore\Model\Exception\NotFoundException as ModelNotFoundException;
use Pimcore\Model\User;
use Pimcore\Model\User\Listing as UserListing;
use Pimcore\Model\UserInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class UserRepository implements UserRepositoryInterface
{
    private const array ASSIGNABLE_USER_TYPES = ['user', 'serviceuser'];

    public function __construct(
        private SecurityServiceInterface $securityService,
        private UserResolverInterface $userResolver,
        private Connection $connection,
        private UserAccountResolverInterface $userAccountResolver,
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

    public function getStoredTwoFactorAuthentication(int $userId): array
    {
        // Not getUserById(): that answers from the request's runtime cache.
        $user = new User();

        try {
            $user->getDao()->getById($userId);
        } catch (ModelNotFoundException) {
            throw new NotFoundException('User', $userId);
        }

        return $user->getTwoFactorAuthentication();
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
        $listing->setCondition('`type` = :type', ['type' => 'user']);
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
                'type = ? AND (name LIKE ? OR firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR id = ?)',
                ['user', $q, $q, $q, $q, (int)$searchQuery]
            );
            $userListing->setOrder('ASC');
            $userListing->setOrderKey('name');
            $userListing->load();

            foreach ($userListing->getUsers() as $user) {
                if ($user->getName() !== 'system') {
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
        if ($names === []) {
            return [];
        }

        return $this->loadAssignableUsers('name', $names, ArrayParameterType::STRING);
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->loadAssignableUsers('id', $ids, ArrayParameterType::INTEGER);
    }

    /**
     * Users and service users matching the values. User\Listing never contains service
     * users, but permission, role and MCP server access assignments have to find them.
     *
     * @param array<int|string> $values
     *
     * @return UserInterface[]
     */
    private function loadAssignableUsers(string $column, array $values, ArrayParameterType $valueType): array
    {
        $ids = $this->connection->fetchFirstColumn(
            'SELECT id FROM users WHERE ' . $column . ' IN (:values) AND `type` IN (:types) AND id > 0',
            ['values' => $values, 'types' => self::ASSIGNABLE_USER_TYPES],
            ['values' => $valueType, 'types' => ArrayParameterType::STRING],
        );

        $users = [];
        foreach ($ids as $id) {
            $user = $this->userAccountResolver->getAccountById((int) $id);
            if ($user !== null) {
                $users[] = $user;
            }
        }

        return $users;
    }
}
