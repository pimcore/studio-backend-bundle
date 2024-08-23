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

use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationFavorite;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationFavoriteRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;

/**
 * @internal
 */
final readonly class FavoriteService implements FavoriteServiceInterface
{
    public function __construct(
        private ConfigurationFavoriteRepositoryInterface $gridConfigurationFavoriteRepository,
        private SecurityServiceInterface $securityService
    )
    {
    }

    public function setAssetConfigurationAsFavoriteForCurrentUser(
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
}