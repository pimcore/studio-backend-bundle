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
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Event\ChartDataEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Event\DrillDownOptionEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Event\ReportEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Event\TreeConfigNodeEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Event\TreeNodeEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Hydrator\CustomReportHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ChartDataParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\DrillDownParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Repository\CustomReportRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportAdd;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportDetails;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ValidateConfigurationTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TypeError;
use function is_string;

/**
 * @internal
 */
final readonly class CustomReportService implements CustomReportServiceInterface
{
    private const string VALUE_KEY = 'value';

    public function __construct(
        private CustomReportHydratorInterface $customReportHydrator,
        private CustomReportRepositoryInterface $customReportRepository,
        private AdapterServiceInterface $adapterService,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getCustomReportTree(): array
    {
        $treeData = [];
        $reportTree = $this->customReportRepository->loadForCurrentUser();

        foreach ($reportTree as $report) {
            $data = $this->customReportHydrator->extractTreeData($report);

            $this->eventDispatcher->dispatch(
                new TreeNodeEvent($data),
                TreeNodeEvent::EVENT_NAME
            );

            $treeData[] = $data;
        }

        return $treeData;
    }

    public function getCustomReportConfigTree(): array
    {
        $treeConfigData = [];
        $reportTree = $this->customReportRepository->loadForCurrentUser();

        foreach ($reportTree as $report) {
            $data = $this->customReportHydrator->extractConfigTreeData($report);

            $this->eventDispatcher->dispatch(
                new TreeConfigNodeEvent($data),
                TreeConfigNodeEvent::EVENT_NAME
            );

            $treeConfigData[] = $data;
        }

        return $treeConfigData;
    }

    /**
     * {@inheritdoc}
     */
    public function createCustomReport(CustomReportAdd $parameters): CustomReportDetails
    {
        $configName = $this->getValidConfigName(['name' => $parameters->getName()]);

        if ($this->customReportRepository->exists($configName)) {
            throw new InvalidArgumentException(
                sprintf('Custom report with name "%s" already exists.', $configName)
            );
        }
        $config = $this->customReportRepository->create($configName);

        return $this->customReportHydrator->extractReportDetails($config);
    }

    public function getCustomReportByName(string $reportName): Config
    {
        return $this->customReportRepository->loadByName($reportName);
    }

    public function getChartData(ChartDataParameter $parameters): Collection
    {
        $reportConfig = $this->getCustomReportByName($parameters->getName());
        $data = $this->adapterService->getData($reportConfig, $parameters);
        $dataEntries = $data['data'] ?? [];
        $hydratedData = [];
        foreach ($dataEntries as $entry) {
            $chartData = $this->customReportHydrator->extractChartData($entry);

            $this->eventDispatcher->dispatch(
                new ChartDataEvent($chartData),
                ChartDataEvent::EVENT_NAME
            );

            $hydratedData[] = $chartData;
        }

        return new Collection($data['total'] ?? 0, $hydratedData);
    }

    public function getCustomReportDetails(string $reportName): CustomReportDetails
    {
        $config = $this->getCustomReportByName($reportName);
        $reportDetails = $this->customReportHydrator->extractReportDetails($config);

        $this->eventDispatcher->dispatch(
            new ReportEvent($reportDetails),
            ReportEvent::EVENT_NAME
        );

        return $reportDetails;
    }

    public function getFieldsForExport(Config $reportConfig): array
    {
        $columns = $reportConfig->getColumnConfiguration();
        $fields = [];
        foreach ($columns as $column) {
            if ($column['export']) {
                $fields[] = $column['name'];
            }
        }

        return $fields;
    }

    public function generateCsvData(array $reportData, array $exportFields, bool $includeHeaders): array
    {
        $csvData = [];

        if (empty($reportData['data'])) {
            return $csvData;
        }

        $data = $reportData['data'];
        if ($includeHeaders) {
            $csvData[] = $exportFields;
        }

        $sortedData = array_map(static function ($row) use ($exportFields) {
            return array_merge(array_flip($exportFields), $row);
        }, $data);

        foreach ($sortedData as $row) {
            $csvData[] = array_values($row);
        }

        return $csvData;
    }

    /**
     * {@inheritdoc}
     */
    public function getDrillDownOptions(DrillDownParameter $parameters): array
    {
        $options = [];
        $config = $this->getCustomReportByName($parameters->getName());
        $adapter = $this->adapterService->getAdapter($config);

        try {
            $data = $adapter->getAvailableOptions(
                $this->sanitizeColumnFilterValues($parameters->getFilters()->getColumnFilters()),
                $parameters->getField(),
                $this->sanitizeDrillDownFilterValues($parameters->getFilters()->getDrillDownFilters())
            );
        } catch (Exception|TypeError $e) {
            throw new DatabaseException($e->getMessage(), $e);
        }

        if (!isset($data['data'])) {
            return $options;
        }

        foreach ($data['data'] as $entry) {
            $option = $this->customReportHydrator->hydrateDrillDownOption($entry);
            $this->eventDispatcher->dispatch(
                new DrillDownOptionEvent($option),
                DrillDownOptionEvent::EVENT_NAME
            );
            $options[] = $option;
        }

        return $options;
    }

    private function sanitizeColumnFilterValues(array $columnFilters): array
    {
        // Ensure all values in column filters are strings
        // This is necessary to ensure compatibility with the core adapter and database queries
        foreach ($columnFilters as $index => $columnFilter) {
            if (!isset($columnFilter[self::VALUE_KEY])) {
                unset($columnFilters[$index]);
            }

            if (!is_string($columnFilter[self::VALUE_KEY])) {
                $columnFilters[$index][self::VALUE_KEY] = (string) $columnFilter[self::VALUE_KEY];
            }
        }

        return $columnFilters;
    }

    private function sanitizeDrillDownFilterValues(array $filters): array
    {
        // Ensure all values in column filters are strings
        // This is necessary to ensure compatibility with the core adapter and database queries
        foreach ($filters as $key => $value) {
            if (!is_string($value)) {
                $filters[$key] = (string)$value;
            }
        }

        return $filters;
    }
}
