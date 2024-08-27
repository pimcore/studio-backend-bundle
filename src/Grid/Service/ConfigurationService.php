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

use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridColumnConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\Event\GridConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\Grid\Hydrator\ConfigurationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;
use function in_array;

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
        private array $predefinedColumns
    ) {
    }

    /**
     * @return ColumnConfiguration[]
     */
    public function getDefaultAssetGridConfiguration(): array
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
                $defaultColumns[] = array_pop($filteredColumns);
            }
        }

        return $defaultColumns;
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
            if($this->userRoleShareService->isConfigurationSharedWithUser($configuration, $currentUser)) {
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
}
