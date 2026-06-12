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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Service;

use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfigurationShare;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SavedSearchParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Model\UserInterface;
use function count;

/**
 * @internal
 */
final readonly class ShareService implements ShareServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function setShareOptions(
        SavedSearchConfiguration $configuration,
        SavedSearchParameter $parameter
    ): SavedSearchConfiguration {
        $configuration->setShareGlobal($parameter->shareGlobal());
        $configuration = $this->addUserShares($configuration, $parameter->getSharedUsers());

        return $this->addRoleShares($configuration, $parameter->getSharedRoles());
    }

    public function isConfigurationAccessibleByUser(
        SavedSearchConfiguration $configuration,
        UserInterface $user
    ): bool {
        if ($configuration->isShareGlobal() || $configuration->getOwner() === $user->getId()) {
            return true;
        }

        if ($this->isUserInSharedUsers($configuration, $user)) {
            return true;
        }

        return $this->isUserInSharedRoles($configuration, $user);
    }

    public function getUserShares(SavedSearchConfiguration $configuration): array
    {
        $shares = $configuration->getShares()->getValues();
        $userShares = [];
        foreach ($shares as $share) {
            try {
                $userShares[] = $this->userRepository->getUserById($share->getUser())->getId();
            } catch (NotFoundException) {
                continue;
            }
        }

        return $userShares;
    }

    public function getRoleShares(SavedSearchConfiguration $configuration): array
    {
        $shares = $configuration->getShares()->getValues();
        $roleShares = [];
        foreach ($shares as $share) {
            try {
                $roleShares[] = $this->roleRepository->getRoleById($share->getUser())->getId();
            } catch (NotFoundException) {
                continue;
            }
        }

        return $roleShares;
    }

    private function addUserShares(
        SavedSearchConfiguration $configuration,
        array $userIds
    ): SavedSearchConfiguration {
        foreach ($userIds as $userId) {
            $user = $this->userRepository->getUserById($userId);
            $configuration->addShare(new SavedSearchConfigurationShare($user->getId(), $configuration));
        }

        return $configuration;
    }

    private function addRoleShares(
        SavedSearchConfiguration $configuration,
        array $roleIds
    ): SavedSearchConfiguration {
        foreach ($roleIds as $roleId) {
            $role = $this->roleRepository->getRoleById($roleId);
            $configuration->addShare(new SavedSearchConfigurationShare($role->getId(), $configuration));
        }

        return $configuration;
    }

    private function isUserInSharedUsers(SavedSearchConfiguration $configuration, UserInterface $user): bool
    {
        /** @var SavedSearchConfigurationShare[] $shares */
        $shares = $configuration->getShares()->getValues();

        foreach ($shares as $share) {
            if ($share->getUser() === $user->getId()) {
                return true;
            }
        }

        return false;
    }

    private function isUserInSharedRoles(SavedSearchConfiguration $configuration, UserInterface $user): bool
    {
        /** @var SavedSearchConfigurationShare[] $shares */
        $shares = $configuration->getShares()->getValues();

        $roles = $user->getRoles();
        foreach ($shares as $share) {
            $filter = array_filter($roles, fn ($role) => $role === $share->getUser());
            if (count($filter) > 0) {
                return true;
            }
        }

        return false;
    }
}
