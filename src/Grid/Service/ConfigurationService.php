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

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Grid\ColumnSchema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\DetailedConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\Hydrator\ConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Hydrator\DetailedConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\DetailedConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class ConfigurationService implements ConfigurationServiceInterface
{
    public function __construct(
        private ColumnConfigurationServiceInterface $columnConfigurationService,
        private ConfigurationRepositoryInterface $configurationRepository,
        private ConfigurationHydratorInterface $configurationHydrator,
        private UserRoleShareServiceInterface $userRoleShareService,
        private SecurityServiceInterface $securityService,
        private EventDispatcherInterface $eventDispatcher,
        private DetailedConfigurationHydratorInterface $detailedConfigurationHydrator,
        private array $predefinedColumns
    ) {
    }

    public function getDefaultAssetGridConfiguration(): DetailedConfiguration
    {
        $availableColumns = $this->columnConfigurationService->getAvailableAssetColumnConfiguration();
        $defaultColumns = [];
        foreach ($this->predefinedColumns as $predefinedColumn) {
            $filteredColumns =
                array_filter($availableColumns, function (ColumnConfiguration $column) use ($predefinedColumn) {
                    if ($column->getKey() === $predefinedColumn['key'] &&
                        $column->getGroup() === $predefinedColumn['group']
                    ) {
                        return true;
                    }

                    return false;
                });

            if (count($filteredColumns) === 1) {
                $column = array_pop($filteredColumns);
                $defaultColumns[] = new ColumnSchema(
                    key: $column->getKey(),
                    locale: $column->getLocale(),
                    group: $column->getGroup(),
                );
            }
        }

        $detailedConfiguration = $this->getDefaultDetailedConfiguration($defaultColumns);

        $this->dispatchEvent($detailedConfiguration);

        return $detailedConfiguration;
    }

    /**
     * @return Configuration[]
     */
    public function getGridConfigurationsForFolder(int $folderId): array
    {
        $configurations = $this->configurationRepository->getByAssetFolderId($folderId);

        $filteredConfigurations = [];
        $currentUser = $this->securityService->getCurrentUser();
        foreach ($configurations as $configuration) {
            if ($this->userRoleShareService->isConfigurationSharedWithUser($configuration, $currentUser)) {
                $hydratedConfiguration = $this->configurationHydrator->hydrate($configuration);

                $this->eventDispatcher->dispatch(
                    new GridConfigurationEvent($hydratedConfiguration),
                    GridConfigurationEvent::EVENT_NAME
                );

                $filteredConfigurations[] = $hydratedConfiguration;
            }
        }

        return $filteredConfigurations;
    }

    public function getAssetGridConfiguration(?int $configurationId, int $folderId): DetailedConfiguration
    {
        if (!$configurationId) {
            return $this->getDefaultAssetGridConfiguration();
        }

        $configuration =  $this->configurationRepository->getById($configurationId);

        $user = $this->securityService->getCurrentUser();
        if (!$this->userRoleShareService->isConfigurationSharedWithUser($configuration, $user)) {
            throw new AccessDeniedException('Access denied to configuration');
        }

        if ($configuration->getAssetFolderId() !== $folderId) {
            throw new InvalidArgumentException('Configuration does not belong to folder');
        }

        $configuration = $this->detailedConfigurationHydrator->hydrate(
            $configuration,
            $this->userRoleShareService->getUserShares($configuration),
            $this->userRoleShareService->getRoleShares($configuration),
            $configuration->isUserFavorite($user)
        );

        $this->dispatchEvent($configuration);

        return $configuration;
    }

    public function dispatchEvent(DetailedConfiguration $detailedConfiguration): void
    {
        $this->eventDispatcher->dispatch(
            new DetailedConfigurationEvent($detailedConfiguration),
            DetailedConfigurationEvent::EVENT_NAME
        );
    }

    private function getDefaultDetailedConfiguration(array $columns): DetailedConfiguration
    {
        return new DetailedConfiguration(
            name: 'Predefined',
            description: 'Default Asset Grid Configuration',
            shareGlobal: false,
            saveFilter: false,
            setAsFavorite: false,
            sharedUsers: [],
            sharedRoles: [],
            columns: $columns,
            filter: [],
        );
    }
}
