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
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Hydrator\CustomReportHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ChartDataParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\DrillDownParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Repository\CustomReportRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
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
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getChartData(ChartDataParameter $parameters): Collection
    {
        $reportConfig = $this->getAllowedReport($parameters->getName());
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

    /**
     * {@inheritdoc}
     */
    public function getDrillDownOptions(DrillDownParameter $parameters): array
    {
        $options = [];
        $config = $this->customReportRepository->loadByName($parameters->getName());
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

    /**
     * @throws ForbiddenException
     */
    private function getAllowedReport(string $reportName): Config
    {
        $report = $this->customReportRepository->loadByNameForCurrentUser($reportName);

        if ($report === null) {
            throw new ForbiddenException('User does not have access to this report');
        }

        return $report;
    }
}
