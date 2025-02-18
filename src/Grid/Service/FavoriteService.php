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

use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationFavorite;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationFavoriteRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;

/**
 * @internal
 */
final readonly class FavoriteService implements FavoriteServiceInterface
{
    public function __construct(
        private ConfigurationFavoriteRepositoryInterface $gridConfigurationFavoriteRepository,
        private UserRoleShareServiceInterface $userRoleShareService,
        private SecurityServiceInterface $securityService
    ) {
    }

    public function setAssetConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration
    ): GridConfiguration {

        $currentUser = $this->securityService->getCurrentUser();
        if (!$this->userRoleShareService->isConfigurationSharedWithUser($gridConfiguration, $currentUser)) {
            throw new ForbiddenException(
                'You are not allowed to set this configuration as favorite.
                You have to be the owner of the configuration or the configuration has to be shared with you.'
            );
        }

        $favorite = $this->gridConfigurationFavoriteRepository->getByUserAndAssetFolder(
            $this->securityService->getCurrentUser()->getId(),
            $gridConfiguration->getAssetFolderId()
        );

        // If there is no favorite for the current user and asset folder, create a new one
        if (!$favorite) {
            $favorite = new GridConfigurationFavorite();
            $favorite->setFolder($gridConfiguration->getAssetFolderId());
            $favorite->setUser($this->securityService->getCurrentUser()->getId());
        }

        $favorite->setConfiguration($gridConfiguration);

        $gridConfiguration->addFavorite($favorite);

        return $gridConfiguration;
    }

    public function setDataObjectConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration,
        int $folderId
    ): GridConfiguration {
        $currentUser = $this->securityService->getCurrentUser();
        if (!$this->userRoleShareService->isConfigurationSharedWithUser($gridConfiguration, $currentUser)) {
            throw new ForbiddenException(
                'You are not allowed to set this configuration as favorite.
                You have to be the owner of the configuration or the configuration has to be shared with you.'
            );
        }

        $favorite = $this->gridConfigurationFavoriteRepository->getByUserAndDataObject(
            $this->securityService->getCurrentUser()->getId(),
            $folderId,
            $gridConfiguration->getClassId()
        );

        // If there is no favorite for the current user folder and classId, create a new one
        if (!$favorite) {
            $favorite = new GridConfigurationFavorite();
            $favorite->setFolder($folderId);
            $favorite->setUser($this->securityService->getCurrentUser()->getId());
            $favorite->setClassId($gridConfiguration->getClassId());
        }

        $favorite->setConfiguration($gridConfiguration);

        $gridConfiguration->addFavorite($favorite);

        return $gridConfiguration;
    }

    public function removeAssetConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration
    ): GridConfiguration {
        $favorite = $this->gridConfigurationFavoriteRepository->getByUserAndAssetFolder(
            $this->securityService->getCurrentUser()->getId(),
            $gridConfiguration->getAssetFolderId()
        );

        if ($favorite) {
            $gridConfiguration->removeFavorite($favorite);
        }

        return $gridConfiguration;
    }

    public function removeDataObjectConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration,
        int $folderId
    ): GridConfiguration {
        $favorite = $this->gridConfigurationFavoriteRepository->getByUserAndDataObject(
            $this->securityService->getCurrentUser()->getId(),
            $folderId,
            $gridConfiguration->getClassId()
        );

        if ($favorite) {
            $gridConfiguration->removeFavorite($favorite);
        }

        return $gridConfiguration;
    }

    public function getFavoriteConfigurationForAssetFolder(int $folderId): ?GridConfiguration
    {
        $favorite = $this->gridConfigurationFavoriteRepository->getByUserAndAssetFolder(
            $this->securityService->getCurrentUser()->getId(),
            $folderId
        );

        return $favorite?->getConfiguration();
    }

    public function getFavoriteConfigurationForDataObject(int $folderId, string $classId): ?GridConfiguration
    {
        $favorite = $this->gridConfigurationFavoriteRepository->getByUserAndDataObject(
            $this->securityService->getCurrentUser()->getId(),
            $folderId,
            $classId
        );

        return $favorite?->getConfiguration();
    }
}
