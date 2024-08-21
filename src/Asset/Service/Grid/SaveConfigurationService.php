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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\Grid;

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\Grid\SaveConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationFavorite;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationShare;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationFavoriteRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;

/**
 * @internal
 */
final readonly class SaveConfigurationService implements SaveConfigurationServiceInterface
{
    public function __construct(
        private ConfigurationRepositoryInterface $gridConfigurationRepository,
        private ConfigurationFavoriteRepositoryInterface $gridConfigurationFavoriteRepository,
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository,
        private AssetServiceInterface $assetService,
        private SecurityServiceInterface $securityService,
    ) {
    }

    /**
     * @throws NotFoundException
     */
    public function saveAssetGridConfiguration(SaveConfigurationParameter $configuration): void
    {
        if (!$this->assetService->assetFolderExists($configuration->getFolderId())) {
            throw new NotFoundException('Asset Folder', $configuration->getFolderId());
        }

        $gridConfiguration = new GridConfiguration();
        $gridConfiguration->setAssetFolderId($configuration->getFolderId());
        $gridConfiguration->setPageSize($configuration->getPageSize());
        $gridConfiguration->setName($configuration->getName());
        $gridConfiguration->setDescription($configuration->getDescription());
        $gridConfiguration->setSaveFilter($configuration->saveFilter());
        $gridConfiguration->setColumns($configuration->getColumnsAsArray());

        if ($configuration->saveFilter()) {
            $gridConfiguration->setFilter($configuration->getFilter()->toArray());
        }

        if ($configuration->setAsFavorite()) {
            $gridConfiguration = $this->setAssetConfigurationAsFavoriteForCurrentUser($gridConfiguration);
        }

        if ($this->securityService->getCurrentUser()->isAllowed('share_configurations')) {
            $gridConfiguration = $this->setShareOptions($gridConfiguration, $configuration);
        }

        $this->gridConfigurationRepository->create($gridConfiguration);
    }

    private function setShareOptions(
        GridConfiguration $configuration,
        SaveConfigurationParameter $options
    ): GridConfiguration {
        $configuration->setShareGlobal($options->shareGlobal());
        $configuration = $this->addUserShareToConfiguration(
            $configuration,
            $options->getSharedUsers()
        );
        $configuration = $this->addRoleShareToConfiguration(
            $configuration,
            $options->getSharedRoles()
        );

        return $configuration;
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

    private function setAssetConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration
    ): GridConfiguration {
        $favorite = $this->gridConfigurationFavoriteRepository->getByUserAndAssetFolder(
            $this->securityService->getCurrentUser()->getId(),
            $gridConfiguration->getAssetFolderId()
        );

        // If there is no favorite for the current user and asset folder, create a new one
        if (!$favorite) {
            $favorite  =  new GridConfigurationFavorite();
            $favorite->setAssetFolder($gridConfiguration->getAssetFolderId());
            $favorite->setUser($this->securityService->getCurrentUser()->getId());
        }

        $favorite->setConfiguration($gridConfiguration);

        $gridConfiguration->addFavorite($favorite);

        return $gridConfiguration;
    }
}
