<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */


namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\Grid\ConfigurationParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationShare;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;

/**
 * @internal
 */
final readonly class UserRoleShareService implements UserRoleShareServiceInterface
{

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository
    )
    {
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

}