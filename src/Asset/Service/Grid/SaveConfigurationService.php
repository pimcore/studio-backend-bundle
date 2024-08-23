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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\FavoriteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\UserRoleShareServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;

/**
 * @internal
 */
final readonly class SaveConfigurationService implements SaveConfigurationServiceInterface
{
    public function __construct(
        private ConfigurationRepositoryInterface $gridConfigurationRepository,
        private FavoriteServiceInterface $favoriteService,
        private UserRoleShareServiceInterface $userRoleShareService,
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
            $gridConfiguration = $this->favoriteService
                ->setAssetConfigurationAsFavoriteForCurrentUser($gridConfiguration);
        }

        if ($this->securityService->getCurrentUser()->isAllowed('share_configurations')) {
            $gridConfiguration = $this->userRoleShareService->setShareOptions($gridConfiguration, $configuration);
        }

        $this->gridConfigurationRepository->create($gridConfiguration);
    }
}
