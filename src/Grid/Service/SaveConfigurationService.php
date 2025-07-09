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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolver;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\Hydrator\ConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\ConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\ConfigurationParameter as DataObjectConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
        private ConfigurationHydratorInterface $configurationHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private ClassDefinitionResolver $classDefinitionResolver
    ) {
    }

    /**
     * @throws NotFoundException
     */
    public function saveAssetGridConfiguration(ConfigurationParameter $configuration): Configuration
    {
        if (!$this->assetService->assetFolderExists($configuration->getFolderId())) {
            throw new NotFoundException('Asset Folder', $configuration->getFolderId());
        }

        $gridConfiguration = new GridConfiguration();
        $gridConfiguration = $this->setDefaultGridConfigurationData($gridConfiguration, $configuration);
        $gridConfiguration->setAssetFolderId($configuration->getFolderId());

        if ($configuration->setAsFavorite()) {
            $gridConfiguration = $this->favoriteService
                ->setAssetConfigurationAsFavoriteForCurrentUser($gridConfiguration, $configuration->getFolderId());
        }

        $gridConfiguration = $this->gridConfigurationRepository->create($gridConfiguration);

        $hydratedConfiguration = $this->configurationHydrator->hydrate($gridConfiguration);

        $this->dispatchEvent($hydratedConfiguration);

        return $hydratedConfiguration;
    }

    /**
     * @throws Exception
     * @throws NotFoundException
     */
    public function saveDataObjectGridConfiguration(
        DataObjectConfigurationParameter $configuration,
        string $classId
    ): Configuration {
        if (!$this->classDefinitionResolver->getById($classId)) {
            throw new NotFoundException('ClassID', $classId);
        }

        $gridConfiguration = new GridConfiguration();
        $gridConfiguration = $this->setDefaultGridConfigurationData($gridConfiguration, $configuration);
        $gridConfiguration->setClassId($classId);

        if ($configuration->setAsFavorite()) {
            $gridConfiguration = $this->favoriteService
                ->setDataObjectConfigurationAsFavoriteForCurrentUser(
                    $gridConfiguration,
                    $configuration->getFolderId()
                );
        }

        $gridConfiguration = $this->gridConfigurationRepository->create($gridConfiguration);

        $hydratedConfiguration = $this->configurationHydrator->hydrate($gridConfiguration);

        $this->dispatchEvent($hydratedConfiguration);

        return $hydratedConfiguration;
    }

    private function setDefaultGridConfigurationData(
        GridConfiguration|DataObjectConfigurationParameter $gridConfiguration,
        ConfigurationParameter $configuration
    ): GridConfiguration {
        $gridConfiguration->setPageSize($configuration->getPageSize());
        $gridConfiguration->setName($configuration->getName());
        $gridConfiguration->setDescription($configuration->getDescription());
        $gridConfiguration->setSaveFilter($configuration->saveFilter());
        $gridConfiguration->setOwner($this->securityService->getCurrentUser()->getId());
        $gridConfiguration->setColumns($configuration->getColumnsAsArray());

        if ($configuration->saveFilter()) {
            $gridConfiguration->setFilter($configuration->getFilter()->toArray());
        }

        if ($this->securityService->getCurrentUser()->isAllowed('share_configurations')) {
            $gridConfiguration = $this->userRoleShareService->setShareOptions($gridConfiguration, $configuration);
        }

        return $gridConfiguration;
    }

    private function dispatchEvent(Configuration $configuration): void
    {
        $this->eventDispatcher->dispatch(
            new GridConfigurationEvent($configuration),
            GridConfigurationEvent::EVENT_NAME
        );
    }
}
