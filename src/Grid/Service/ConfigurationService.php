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
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class ConfigurationService implements ConfigurationServiceInterface
{
    public function __construct(
        private GridServiceInterface $gridService,
        private EventDispatcherInterface $eventDispatcher,
        private array $predefinedColumns
    )
    {
    }

    /**
     * @return ColumnConfiguration[]
     */
    public function getAvailableAssetGridConfiguration(): array
    {
        $columns = [];
        foreach ($this->gridService->getColumnCollectors() as $collector) {
            // Only collect supported asset collectors
            if (!in_array(ElementTypes::TYPE_ASSET, $collector->supportedElementTypes(), true)) {
                continue;
            }

            $columns = array_merge(
                $columns,
                $collector->getColumnConfigurations(
                    $this->gridService->getColumnDefinitions()
                )
            );
        }

        foreach ($columns as $column) {
            $this->eventDispatcher->dispatch(
                new GridColumnConfigurationEvent($column),
                GridColumnConfigurationEvent::EVENT_NAME
            );
        }

        return $columns;
    }

    /**
     * @return ColumnConfiguration[]
     */
    public function getDefaultAssetGridConfiguration(): array
    {
        $availableColumns = $this->getAvailableAssetGridConfiguration();
        $defaultColumns = [];
        foreach ($this->predefinedColumns as $predefinedColumn) {
            $filteredColumns = array_filter($availableColumns, function (ColumnConfiguration $column) use ($predefinedColumn) {
                if ($column->getKey() === $predefinedColumn['key'] && $column->getGroup() === $predefinedColumn['group']) {
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
}