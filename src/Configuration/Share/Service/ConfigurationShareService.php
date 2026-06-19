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

namespace Pimcore\Bundle\StudioBackendBundle\Configuration\Share\Service;

use Pimcore\Bundle\StudioBackendBundle\Configuration\Share\ShareableConfigurationInterface;
use Pimcore\Bundle\StudioBackendBundle\Configuration\Share\ShareOptionsInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Model\UserInterface;
use function count;

/**
 * @internal
 */
final readonly class ConfigurationShareService implements ConfigurationShareServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function setShareOptions(
        ShareableConfigurationInterface $configuration,
        ShareOptionsInterface $options,
    ): ShareableConfigurationInterface {
        $configuration->setShareGlobal($options->shareGlobal());
        $configuration = $this->addUserShares($configuration, $options->getSharedUsers());

        return $this->addRoleShares($configuration, $options->getSharedRoles());
    }

    public function isConfigurationSharedWithUser(
        ShareableConfigurationInterface $configuration,
        UserInterface $user,
    ): bool {
        if ($configuration->isShareGlobal() || $configuration->getOwner() === $user->getId()) {
            return true;
        }

        if ($this->isUserInSharedUsers($configuration, $user)) {
            return true;
        }

        return $this->isUserInSharedRoles($configuration, $user);
    }

    public function getUserShares(ShareableConfigurationInterface $configuration): array
    {
        $userShares = [];
        foreach ($configuration->getShares()->getValues() as $share) {
            try {
                $userShares[] = $this->userRepository->getUserById($share->getUser())->getId();
            } catch (NotFoundException) {
                continue;
            }
        }

        return $userShares;
    }

    public function getRoleShares(ShareableConfigurationInterface $configuration): array
    {
        $roleShares = [];
        foreach ($configuration->getShares()->getValues() as $share) {
            try {
                $roleShares[] = $this->roleRepository->getRoleById($share->getUser())->getId();
            } catch (NotFoundException) {
                continue;
            }
        }

        return $roleShares;
    }

    /**
     * @throws NotFoundException
     */
    private function addUserShares(
        ShareableConfigurationInterface $configuration,
        array $userIds,
    ): ShareableConfigurationInterface {
        foreach ($userIds as $userId) {
            $user = $this->userRepository->getUserById($userId);
            $configuration->addShare($configuration->createShare($user->getId()));
        }

        return $configuration;
    }

    /**
     * @throws NotFoundException
     */
    private function addRoleShares(
        ShareableConfigurationInterface $configuration,
        array $roleIds,
    ): ShareableConfigurationInterface {
        foreach ($roleIds as $roleId) {
            $role = $this->roleRepository->getRoleById($roleId);
            $configuration->addShare($configuration->createShare($role->getId()));
        }

        return $configuration;
    }

    private function isUserInSharedUsers(
        ShareableConfigurationInterface $configuration,
        UserInterface $user,
    ): bool {
        foreach ($configuration->getShares()->getValues() as $share) {
            if ($share->getUser() === $user->getId()) {
                return true;
            }
        }

        return false;
    }

    private function isUserInSharedRoles(
        ShareableConfigurationInterface $configuration,
        UserInterface $user,
    ): bool {
        $roles = $user->getRoles();
        foreach ($configuration->getShares()->getValues() as $share) {
            $filter = array_filter($roles, static fn ($role) => $role === $share->getUser());
            if (count($filter) > 0) {
                return true;
            }
        }

        return false;
    }
}
