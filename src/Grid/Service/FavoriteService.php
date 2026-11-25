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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Configuration\Share\Service\ConfigurationShareServiceInterface;
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
    /**
     * Folder value of the favorite row that holds the default of a user and class.
     * It is shared by all data objects and folders of that class; no element has the id 0.
     */
    private const int SHARED_DEFAULT_FOLDER = 0;

    public function __construct(
        private ConfigurationFavoriteRepositoryInterface $gridConfigurationFavoriteRepository,
        private ConfigurationShareServiceInterface $shareService,
        private SecurityServiceInterface $securityService
    ) {
    }

    public function setAssetConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration,
        int $folderId
    ): GridConfiguration {

        $currentUser = $this->securityService->getCurrentUser();
        if (!$this->shareService->isConfigurationSharedWithUser($gridConfiguration, $currentUser)) {
            throw new ForbiddenException(
                'You are not allowed to set this configuration as favorite.
                You have to be the owner of the configuration or the configuration has to be shared with you.'
            );
        }

        $favorite = $this->gridConfigurationFavoriteRepository->getByUserAndAssetFolder(
            $this->securityService->getCurrentUser()->getId(),
            $folderId
        );

        // If there is no favorite for the current user and asset folder, create a new one
        if (!$favorite) {
            $favorite = new GridConfigurationFavorite();
            $favorite->setFolder($folderId);
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
        if (!$this->shareService->isConfigurationSharedWithUser($gridConfiguration, $currentUser)) {
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

        $this->setSharedDefaultForCurrentUser($gridConfiguration);

        return $gridConfiguration;
    }

    public function removeAssetConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration,
        int $folderId
    ): GridConfiguration {
        $favorite = $this->gridConfigurationFavoriteRepository->getByUserAndAssetFolder(
            $this->securityService->getCurrentUser()->getId(),
            $folderId
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

        $this->removeSharedDefaultForCurrentUser($gridConfiguration);

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
        $userId = $this->securityService->getCurrentUser()->getId();

        // A favorite of this very data object or folder wins over the default shared by its class
        $favorite = $this->gridConfigurationFavoriteRepository->getByUserAndDataObject(
            $userId,
            $folderId,
            $classId
        ) ?? $this->gridConfigurationFavoriteRepository->getByUserAndDataObject(
            $userId,
            self::SHARED_DEFAULT_FOLDER,
            $classId
        );

        return $favorite?->getConfiguration();
    }

    private function setSharedDefaultForCurrentUser(GridConfiguration $gridConfiguration): void
    {
        $userId = $this->securityService->getCurrentUser()->getId();
        $shared = $this->gridConfigurationFavoriteRepository->getByUserAndDataObject(
            $userId,
            self::SHARED_DEFAULT_FOLDER,
            $gridConfiguration->getClassId()
        );

        // Compared by instance: a configuration that is saved together with its favorite has no id yet
        if ($shared?->getConfiguration() === $gridConfiguration) {
            return;
        }

        // The configuration is part of the row's identifier, so the row is replaced, not updated
        $shared?->getConfiguration()->removeFavorite($shared);

        $shared = new GridConfigurationFavorite();
        $shared->setFolder(self::SHARED_DEFAULT_FOLDER);
        $shared->setUser($userId);
        $shared->setClassId($gridConfiguration->getClassId());
        $shared->setConfiguration($gridConfiguration);

        $gridConfiguration->addFavorite($shared);
    }

    private function removeSharedDefaultForCurrentUser(GridConfiguration $gridConfiguration): void
    {
        $shared = $this->gridConfigurationFavoriteRepository->getByUserAndDataObject(
            $this->securityService->getCurrentUser()->getId(),
            self::SHARED_DEFAULT_FOLDER,
            $gridConfiguration->getClassId()
        );

        if ($shared?->getConfiguration() === $gridConfiguration) {
            $shared->getConfiguration()->removeFavorite($shared);
        }
    }
}
