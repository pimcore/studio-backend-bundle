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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Service;

use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Event\ChartDataEvent;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Event\ReportEvent;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Event\TreeConfigNodeEvent;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Event\TreeNodeEvent;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Hydrator\CustomReportHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Repository\CustomReportRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportChartData;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportDetails;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class CustomReportService implements CustomReportServiceInterface
{
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

    public function getCustomReportByName(string $reportName): Config
    {
        return $this->customReportRepository->loadByName($reportName);
    }

    public function getChartData(string $reportName, ExportParameter $chartDataParameter): CustomReportChartData
    {
        $reportConfig = $this->getCustomReportByName($reportName);
        $data = $this->adapterService->getData($reportConfig, $chartDataParameter);
        $chartData = $this->customReportHydrator->extractChartData($data);

        $this->eventDispatcher->dispatch(
            new ChartDataEvent($chartData),
            ChartDataEvent::EVENT_NAME
        );

        return $chartData;
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
        if ($includeHeaders) {
            $csvData[] = $exportFields;
        }

        foreach ($reportData['data'] ?? [] as $row) {
            $csvData[] = array_values($row);
        }

        return $csvData;
    }
}
