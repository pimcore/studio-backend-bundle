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
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\Grid\UpdateConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationFavorite;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationShare;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationFavoriteRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\FavoriteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\UserRoleShareServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;

/**
 * @internal
 */
final readonly class UpdateConfigurationService implements UpdateConfigurationServiceInterface
{
    public function __construct(
        private ConfigurationRepositoryInterface $gridConfigurationRepository,
        private SecurityServiceInterface $securityService,
        private FavoriteServiceInterface $favoriteService,
        private UserRoleShareServiceInterface $userRoleShareService,
    ) {
    }

    /**
     * @throws NotFoundException
     */
    public function updateAssetGridConfigurationById(UpdateConfigurationParameter $configurationParams, int $id): void
    {
        $configuration = $this->gridConfigurationRepository->getById($id);

        if ($configuration->getOwner() !== $this->securityService->getCurrentUser()->getId()) {
            throw new ForbiddenException("You are not allowed to update this configuration.");
        }

        $configuration = $this->gridConfigurationRepository->clearShares($configuration);

        $configuration->setPageSize($configurationParams->getPageSize());
        $configuration->setName($configurationParams->getName());
        $configuration->setDescription($configurationParams->getDescription());
        $configuration->setSaveFilter($configurationParams->saveFilter());
        $configuration->setColumns($configurationParams->getColumnsAsArray());

        $configuration->setFilter(null);
        if ($configurationParams->saveFilter()) {
            $configuration->setFilter($configurationParams->getFilter()->toArray());
        }


        if ($configurationParams->setAsFavorite()) {
            $configuration = $this->favoriteService
                ->setAssetConfigurationAsFavoriteForCurrentUser($configuration);
        }

        if (!$configurationParams->setAsFavorite()) {
            $configuration = $this->favoriteService
                ->removeAssetConfigurationAsFavoriteForCurrentUser($configuration);
        }

        if ($this->securityService->getCurrentUser()->isAllowed('share_configurations')) {
            $configuration = $this->userRoleShareService->setShareOptions($configuration, $configurationParams);
        }

        $this->gridConfigurationRepository->update($configuration);
    }
}
