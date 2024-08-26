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
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationShare;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationFavoriteRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use function count;

/**
 * @internal
 */
final readonly class FavoriteService implements FavoriteServiceInterface
{
    public function __construct(
        private ConfigurationFavoriteRepositoryInterface $gridConfigurationFavoriteRepository,
        private SecurityServiceInterface $securityService
    ) {
    }

    public function setAssetConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration
    ): GridConfiguration {

        if (!$this->isCurrentUserAllowsToSetAsFavorite($gridConfiguration)) {
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

    private function isCurrentUserAllowsToSetAsFavorite(GridConfiguration $gridConfiguration): bool
    {
        if ($gridConfiguration->getOwner() === $this->securityService->getCurrentUser()->getId()) {
            return true;
        }

        if ($this->isCurrentUserInSharedUsers($gridConfiguration)) {
            return true;
        }

        if ($this->isCurrentUserInSharedRoles($gridConfiguration)) {
            return true;
        }

        return false;
    }

    private function isCurrentUserInSharedUsers(GridConfiguration $gridConfiguration): bool
    {
        /** @var GridConfigurationShare[] $shares */
        $shares = $gridConfiguration->getShares()->getValues();

        foreach ($shares as $share) {
            if ($share->getUser() === $this->securityService->getCurrentUser()->getId()) {
                return true;
            }
        }

        return false;
    }

    private function isCurrentUserInSharedRoles(GridConfiguration $gridConfiguration): bool
    {
        /** @var GridConfigurationShare[] $shares */
        $shares = $gridConfiguration->getShares()->getValues();

        $roles = $this->securityService->getCurrentUser()->getRoles();
        foreach ($shares as $share) {
            $filter = array_filter($roles, fn ($role) => $role === $share->getUser());
            if (count($filter) > 0) {
                return true;
            }
        }

        return false;
    }
}
