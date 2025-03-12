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
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\DetailedConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\Hydrator\ConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Hydrator\DetailedConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\DetailedConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
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
        private FavoriteServiceInterface $favoriteService,
        private array $assetPredefinedColumns,
        private array $dataObjectPredefinedColumns
    ) {
    }

    public function getConfigurationsForAssetsByFolder(int $folderId): Collection
    {
        $configurations = $this->configurationRepository->getByAssetFolderId($folderId);

        $filteredConfigurations = $this->filterConfigurationsForCurrentUser($configurations);

        return new Collection(count($filteredConfigurations), $filteredConfigurations);
    }

    public function getConfigurationsForDataObjectsByClassId(string $classId): Collection
    {
        $configurations = $this->configurationRepository->getByClassId($classId);

        $filteredConfigurations = $this->filterConfigurationsForCurrentUser($configurations);

        return new Collection(count($filteredConfigurations), $filteredConfigurations);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssetGridConfiguration(?int $configurationId, int $folderId): DetailedConfiguration
    {
        if (!$configurationId) {
            $configuration = $this->favoriteService->getFavoriteConfigurationForAssetFolder($folderId);
            $configurationId = $configuration?->getId();
        }

        if (!$configurationId) {
            return $this->getDefaultAssetGridConfiguration();
        }

        $configuration =  $this->configurationRepository->getById($configurationId);

        $user = $this->securityService->getCurrentUser();
        if ($configuration->getAssetFolderId() !== $folderId) {
            return $this->getDefaultAssetGridConfiguration();
        }

        if (!$this->userRoleShareService->isConfigurationSharedWithUser($configuration, $user)) {
            return $this->getDefaultAssetGridConfiguration();
        }

        $configuration = $this->detailedConfigurationHydrator->hydrate(
            $configuration,
            $this->userRoleShareService->getUserShares($configuration),
            $this->userRoleShareService->getRoleShares($configuration),
            $configuration->isUserFavorite($user)
        );

        $this->dispatchDetailedConfigurationEvent($configuration);

        return $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataObjectGridConfiguration(
        ?int $configurationId,
        int $folderId,
        string $classId
    ): DetailedConfiguration {
        if (!$configurationId) {
            $configuration = $this->favoriteService->getFavoriteConfigurationForDataObject($folderId, $classId);
            $configurationId = $configuration?->getId();
        }

        if (!$configurationId) {
            return $this->getDefaultDataObjectGridConfiguration($folderId, $classId);
        }

        $configuration =  $this->configurationRepository->getById($configurationId);
        $user = $this->securityService->getCurrentUser();

        if ($configuration->getClassId() !== $classId) {
            return $this->getDefaultDataObjectGridConfiguration($folderId, $classId);
        }

        if (!$this->userRoleShareService->isConfigurationSharedWithUser($configuration, $user)) {
            return $this->getDefaultDataObjectGridConfiguration($folderId, $classId);
        }

        $configuration = $this->detailedConfigurationHydrator->hydrate(
            $configuration,
            $this->userRoleShareService->getUserShares($configuration),
            $this->userRoleShareService->getRoleShares($configuration),
            $configuration->isUserFavorite($user)
        );

        $this->dispatchDetailedConfigurationEvent($configuration);

        return $configuration;
    }

    /**
     * @throws ForbiddenException|InvalidArgumentException|NotFoundException
     */
    public function deleteAssetConfiguration(int $configurationId, int $folderId): void
    {
        $configuration = $this->configurationRepository->getById($configurationId);

        if ($configuration->getAssetFolderId() !== $folderId) {
            throw new InvalidArgumentException('Configuration does not belong to folder');
        }

        if ($this->securityService->getCurrentUser()->getId() !== $configuration->getOwner()) {
            throw new ForbiddenException(
                'You are not allowed to delete this configuration. Only the owner can delete it.'
            );
        }

        $this->configurationRepository->delete($configuration);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDataObjectConfiguration(int $configurationId): void
    {
        $configuration = $this->configurationRepository->getById($configurationId);

        if (!$configuration->getClassId()) {
            throw new NotFoundException('Configuration', $configurationId);
        }

        if ($this->securityService->getCurrentUser()->getId() !== $configuration->getOwner()) {
            throw new ForbiddenException(
                'You are not allowed to delete this configuration. Only the owner can delete it.'
            );
        }

        $this->configurationRepository->delete($configuration);
    }

    private function getDefaultDetailedConfiguration(array $columns): DetailedConfiguration
    {
        return new DetailedConfiguration(
            name: 'Predefined',
            description: 'Default Grid Configuration',
            shareGlobal: false,
            saveFilter: false,
            setAsFavorite: false,
            sharedUsers: [],
            sharedRoles: [],
            columns: $columns,
            filter: [],
        );
    }

    private function getDefaultAssetGridConfiguration(): DetailedConfiguration
    {
        $availableColumns = $this->columnConfigurationService->getAvailableAssetColumnConfiguration();

        return $this->buildDefaultConfiguration($availableColumns, $this->assetPredefinedColumns);
    }

    private function getDefaultDataObjectGridConfiguration(int $folderId, string $classId): DetailedConfiguration
    {
        $availableColumns = $this->columnConfigurationService->getAvailableDataObjectColumnConfiguration(
            $classId,
            $folderId
        );

        return $this->buildDefaultConfiguration(
            $availableColumns,
            $this->dataObjectPredefinedColumns,
            false,
            true
        );
    }

    /**
     * @param ColumnConfiguration[] $availableColumns
     *
     */
    public function buildDefaultConfiguration(
        array $availableColumns,
        array $predefinedColumns,
        bool $search = false,
        bool $grid = false
    ): DetailedConfiguration {
        $defaultColumns = [];
        foreach ($predefinedColumns as $predefinedColumn) {
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

        $defaultColumns = [
            ...$defaultColumns,
            ...$this->getDefaultColumnsForSearchAndGrid($availableColumns, $search, $grid),
        ];

        $detailedConfiguration = $this->getDefaultDetailedConfiguration($defaultColumns);

        $this->dispatchDetailedConfigurationEvent($detailedConfiguration);

        return $detailedConfiguration;
    }

    /**
     * @param ColumnConfiguration[] $availableColumns
     *
     * @return ColumnSchema[]
     */
    private function getDefaultColumnsForSearchAndGrid(array $availableColumns, bool $search, bool $grid): array
    {
        $defaultColumns = [];
        foreach ($availableColumns as $column) {
            if (
                !isset($column->getConfig()['fieldDefinition']) ||
                !$column->getConfig()['fieldDefinition'] instanceof Data) {
                continue;
            }

            /** @var Data $fieldDefinition */
            $fieldDefinition = $column->getConfig()['fieldDefinition'];
            if ($search && !$fieldDefinition->getVisibleSearch()) {
                $defaultColumns[] = new ColumnSchema(
                    key: $column->getKey(),
                    locale: $column->getLocale(),
                    group: $column->getGroup(),
                );

                continue;
            }

            if ($grid && $fieldDefinition->getVisibleGridView()) {
                $defaultColumns[] = new ColumnSchema(
                    key: $column->getKey(),
                    locale: $column->getLocale(),
                    group: $column->getGroup(),
                );
            }
        }

        return $defaultColumns;
    }

    /**
     * @param GridConfiguration[] $configurations
     *
     * @return Configuration[]
     */
    private function filterConfigurationsForCurrentUser(array $configurations): array
    {
        $filteredConfigurations = [];
        $currentUser = $this->securityService->getCurrentUser();
        foreach ($configurations as $configuration) {
            if ($this->userRoleShareService->isConfigurationSharedWithUser($configuration, $currentUser)) {
                $hydratedConfiguration = $this->configurationHydrator->hydrate($configuration);

                $this->dispatchConfigurationEvent($hydratedConfiguration);

                $filteredConfigurations[] = $hydratedConfiguration;
            }
        }

        return $filteredConfigurations;
    }

    public function dispatchDetailedConfigurationEvent(DetailedConfiguration $detailedConfiguration): void
    {
        $this->eventDispatcher->dispatch(
            new DetailedConfigurationEvent($detailedConfiguration),
            DetailedConfigurationEvent::EVENT_NAME
        );
    }

    private function dispatchConfigurationEvent(Configuration $configuration): void
    {
        $this->eventDispatcher->dispatch(
            new GridConfigurationEvent($configuration),
            GridConfigurationEvent::EVENT_NAME
        );
    }
}
