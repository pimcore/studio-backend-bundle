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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ParentIdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Event\SimpleUserEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Event\UserEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Event\UserTreeNodeEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\SimpleUserHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserTreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UserWithPermissionParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserFolderRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\User as UserSchema;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;
use function sprintf;

/**
 * @internal
 */
final readonly class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserTreeNodeHydratorInterface $userTreeNodeHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
        private UserFolderRepositoryInterface $userFolderRepository,
        private UserHydratorInterface $userHydrator,
        private SimpleUserHydratorInterface $simpleUserHydrator
    ) {
    }

    public function getUserTreeListing(ParentIdParameter $userListParameter): Collection
    {
        $userListing = $this->userRepository->getUserListingByParentId($userListParameter->getParentId());
        $users = [];

        foreach ($userListing->getUsers() as $user) {
            if ($user->getName() === 'system') {
                continue;
            }

            $userTreeNode = $this->userTreeNodeHydrator->hydrate($user);

            $this->eventDispatcher->dispatch(
                new UserTreeNodeEvent($userTreeNode),
                UserTreeNodeEvent::EVENT_NAME
            );

            $users[] = $userTreeNode;
        }

        return new Collection(
            totalItems: $userListing->getTotalCount(),
            items: $users
        );
    }

    /**
     * @throws NotFoundException|ForbiddenException|DatabaseException
     */
    public function deleteUser(int $userId): void
    {
        $currentUser = $this->securityService->getCurrentUser();
        $userToDelete = $this->userRepository->getUserById($userId);

        if (!$currentUser->isAdmin() && $userToDelete->isAdmin()) {
            throw new ForbiddenException('Only admins can delete other admins');
        }

        try {
            $this->userRepository->deleteUser($userToDelete);
        } catch (Exception $exception) {
            throw new DatabaseException(
                sprintf(
                    'Error deleting user with id %d: %s',
                    $userId,
                    $exception->getMessage()
                )
            );
        }

    }

    /**
     * @throws DatabaseException
     */
    public function getUsers(): Collection
    {
        $users = $this->userRepository->getUsers();

        return $this->getSimpleUserCollection($users);
    }

    /**
     * @throws DatabaseException
     */
    public function getUsersWithPermission(UserWithPermissionParameter $parameter): Collection
    {
        $users = $this->userRepository->getUsersWithPermission(
            $parameter->getPermission(),
            $parameter->isIncludeCurrentUser()
        );

        return $this->getSimpleUserCollection($users);
    }

    /**
     * @throws NotFoundException|DatabaseException
     */
    public function createUser(CreateParameter $createParameter): TreeNode
    {
        $folderId = 0;

        // Check if the parent folder exists
        if ($createParameter->getParentId() !== 0) {
            $folderId = $this->userFolderRepository->getUserFolderById($createParameter->getParentId())->getId();
        }

        try {
            $user = $this->userRepository->createUser($createParameter->getName(), $folderId);
        } catch (Exception $exception) {
            throw new DatabaseException(
                sprintf(
                    'Error creating user: %s',
                    $exception->getMessage()
                )
            );
        }

        return $this->userTreeNodeHydrator->hydrate($user);
    }

    public function getUserById(int $userId): UserSchema
    {
        $user = $this->userRepository->getUserById($userId);

        if ($user->isAdmin() && !$this->securityService->getCurrentUser()->isAdmin()) {
            throw new ForbiddenException('Only admins can view other admins');
        }

        $user = $this->userHydrator->hydrate($user);

        $this->eventDispatcher->dispatch(
            new UserEvent($user),
            UserEvent::EVENT_NAME
        );

        return $user;
    }

    /**
     * @throws DatabaseException
     */
    public function userSearch(string $searchQuery): Collection
    {
        $users = $this->userRepository->searchUser($searchQuery);

        return $this->getSimpleUserCollection($users);
    }

    public function getUserNameById(int $userId): ?string
    {
        try {
            return $this->userRepository->getUserById($userId)->getName();
        } catch (NotFoundException) {
            return null;
        }
    }

    public function getUserNamesByIds(array $userIds): array
    {
        $names = [];
        foreach ($this->userRepository->getUsersByIds($userIds) as $user) {
            $names[$user->getId()] = $user->getName();
        }

        return $names;
    }

    /**
     * @param UserInterface[] $users
     */
    private function getSimpleUserCollection(array $users): Collection
    {
        $items = [];

        foreach ($users as $user) {
            $item = $this->simpleUserHydrator->hydrate($user);

            $this->eventDispatcher->dispatch(
                new SimpleUserEvent($item),
                SimpleUserEvent::EVENT_NAME
            );

            $items[] = $item;
        }

        return new Collection(count($items), $items);
    }
}
