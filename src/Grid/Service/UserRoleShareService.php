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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\Grid\ConfigurationParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationShare;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Model\UserInterface;
use function count;

/**
 * @internal
 */
final readonly class UserRoleShareService implements UserRoleShareServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository
    ) {
    }

    public function setShareOptions(
        GridConfiguration $configuration,
        ConfigurationParameterInterface $options
    ): GridConfiguration {
        $configuration->setShareGlobal($options->shareGlobal());
        $configuration = $this->addUserShareToConfiguration(
            $configuration,
            $options->getSharedUsers()
        );

        return $this->addRoleShareToConfiguration(
            $configuration,
            $options->getSharedRoles()
        );
    }

    public function isConfigurationSharedWithUser(GridConfiguration $gridConfiguration, UserInterface $user): bool
    {
        if ($gridConfiguration->getOwner() === $user->getId()) {
            return true;
        }

        if ($this->isUserInSharedUsers($gridConfiguration, $user)) {
            return true;
        }

        if ($this->isUserInSharedRoles($gridConfiguration, $user)) {
            return true;
        }

        return false;
    }

    public function getUserShares(GridConfiguration $gridConfiguration): array
    {
        $shares = $gridConfiguration->getShares()->getValues();
        $userShares = [];
        foreach ($shares as $share) {
            try {
                $userShares[] = $this->userRepository->getUserById($share->getUser())->getId();
            } catch (NotFoundException $e) {
                continue;
            }
        }

        return $userShares;
    }

    public function getRoleShares(GridConfiguration $gridConfiguration): array
    {
        $shares = $gridConfiguration->getShares()->getValues();
        $roleShares = [];
        foreach ($shares as $share) {
            try {
                $roleShares[] = $this->roleRepository->getRoleById($share->getUser())->getId();
            } catch (NotFoundException $e) {
                continue;
            }
        }

        return $roleShares;
    }


    /**
     * @throws NotFoundException
     */
    private function addUserShareToConfiguration(
        GridConfiguration $gridConfiguration,
        array $userIds
    ): GridConfiguration {
        foreach ($userIds as $userId) {
            // Check if user exists
            $user = $this->userRepository->getUserById($userId);
            $share = new GridConfigurationShare($user->getId(), $gridConfiguration);
            $gridConfiguration->addShare($share);
        }

        return $gridConfiguration;
    }

    /**
     * @throws NotFoundException
     */
    private function addRoleShareToConfiguration(
        GridConfiguration $gridConfiguration,
        array $roleIds
    ): GridConfiguration {
        foreach ($roleIds as $roleId) {
            // Check if role exists
            $role = $this->roleRepository->getRoleById($roleId);
            $share = new GridConfigurationShare($role->getId(), $gridConfiguration);
            $gridConfiguration->addShare($share);
        }

        return $gridConfiguration;
    }

    private function isUserInSharedUsers(GridConfiguration $gridConfiguration, UserInterface $user): bool
    {
        /** @var GridConfigurationShare[] $shares */
        $shares = $gridConfiguration->getShares()->getValues();

        foreach ($shares as $share) {
            if ($share->getUser() === $user->getId()) {
                return true;
            }
        }

        return false;
    }

    private function isUserInSharedRoles(GridConfiguration $gridConfiguration, UserInterface $user): bool
    {
        /** @var GridConfigurationShare[] $shares */
        $shares = $gridConfiguration->getShares()->getValues();

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
