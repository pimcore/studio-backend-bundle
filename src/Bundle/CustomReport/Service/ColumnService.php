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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Event\ColumnInformationEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Hydrator\ColumnHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportDataSourceConfig;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class ColumnService implements ColumnServiceInterface
{
    public function __construct(
        private AdapterServiceInterface $adapterService,
        private CustomReportConfigServiceInterface $customReportConfigService,
        private ColumnHydratorInterface $columnHydrator,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnConfig(string $name, CustomReportDataSourceConfig $dataSourceConfig): array
    {
        $report = $this->customReportConfigService->getAllowedReport($name);
        $columns = $this->adapterService->getAdapterColumns($dataSourceConfig->getConfiguration());
        $hydrated = [];

        try {
            $orderedColumns = $this->orderColumnsByConfiguration($columns, $report->getColumnConfiguration());
            foreach ($orderedColumns as $column) {
                $hydratedColumn = $this->columnHydrator->hydrateColumnInfo($column);
                $this->eventDispatcher->dispatch(
                    new ColumnInformationEvent($hydratedColumn),
                    ColumnInformationEvent::EVENT_NAME
                );
                $hydrated[] = $hydratedColumn;
            }
        } catch (Exception $e) {
            throw new DatabaseException(
                sprintf('Failed to get column configuration for report "%s": %s', $name, $e->getMessage()),
                previous: $e
            );
        }

        return $hydrated;
    }

    private function orderColumnsByConfiguration(array $columns, array $columnConfiguration): array
    {
        $columnMap = $this->mapColumnsByName($columns);
        $result = [];

        foreach ($columnConfiguration as $configItem) {
            $colName = $configItem['name'] ?? null;
            if ($colName && isset($columnMap[$colName])) {
                $result[] = $columnMap[$colName];
                unset($columnMap[$colName]);
            }
        }

        return array_merge($result, $columnMap);
    }

    private function mapColumnsByName(array $columns): array
    {
        $columnNames = array_map(static fn ($column) => $column->getName(), $columns);

        return array_combine($columnNames, $columns);
    }
}
