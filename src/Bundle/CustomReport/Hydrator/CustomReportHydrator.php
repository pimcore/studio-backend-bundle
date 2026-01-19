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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Hydrator;

use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportChartData;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportDetails;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportDrillDownOption;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportTreeConfigNode;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportTreeNode;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportUpdate;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\AdapterServiceInterface;

/**
 * @internal
 */
final readonly class CustomReportHydrator implements CustomReportHydratorInterface
{
    public function __construct(
        private ColumnHydratorInterface $columnHydrator,
        private readonly AdapterServiceInterface $adapterService
    ) {
    }

    public function extractTreeData(Config $report): CustomReportTreeNode
    {
        return new CustomReportTreeNode(
            htmlspecialchars($report->getName()),
            htmlspecialchars($report->getNiceName()),
            htmlspecialchars($report->getIconClass()),
            htmlspecialchars($report->getGroup()),
            htmlspecialchars($report->getGroupIconClass()),
            $report->getMenuShortcut(),
            htmlspecialchars($report->getReportClass()),
            (bool)$report->getDataSourceConfig()
        );
    }

    public function extractConfigTreeData(Config $report): CustomReportTreeConfigNode
    {
        return new CustomReportTreeConfigNode(
            $report->getName(),
            $report->getName(),
            'pimcore_treenode_disabled',
            $report->isWriteable()
        );
    }

    public function extractReportDetails(Config $report): CustomReportDetails
    {
        return new CustomReportDetails(
            $report->getName(),
            $report->getSql(),
            $this->columnHydrator->getCustomReportColumnConfiguration($report),
            $report->getNiceName(),
            $report->getGroup(),
            $report->getGroupIconClass(),
            $report->getIconClass(),
            $report->getMenuShortcut(),
            $report->getReportClass(),
            $report->getChartType(),
            $report->getModificationDate(),
            $report->getCreationDate(),
            $report->getSharedUserNames(),
            $report->getSharedRoleNames(),
            $report->getShareGlobally(),
            $report->isWriteable(),
            $report->getDataSourceConfig(),
            $report->getPieColumn(),
            $report->getPieLabelColumn(),
            $report->getXAxis(),
            $report->getYAxis(),
        );
    }

    public function extractChartData(array $chartData): CustomReportChartData
    {
        return new CustomReportChartData($chartData);
    }

    public function hydrateDrillDownOption(array $drillDownData): CustomReportDrillDownOption
    {
        return new CustomReportDrillDownOption(
            $drillDownData['name'] ?? '',
            $drillDownData['value'] ?? ''
        );
    }

    public function dehydrateReportDetails(
        Config $config,
        CustomReportUpdate $customReportUpdate
    ): Config {
        $adapter = $this->adapterService->getAdapter($config);

        $config->setSql($customReportUpdate->getSql());
        $config->setColumnConfiguration(
            $this->columnHydrator->dehydrateColumnConfiguration(
                $customReportUpdate->getColumnConfigurations()
            )
        );
        $config->setDataSourceConfig($customReportUpdate->getDataSourceConfig());
        $config->setNiceName($customReportUpdate->getNiceName());
        $config->setGroup($customReportUpdate->getGroup());
        $config->setGroupIconClass($customReportUpdate->getGroupIconClass());
        $config->setIconClass($customReportUpdate->getIconClass());
        $config->setMenuShortcut($customReportUpdate->getMenuShortcut());
        $config->setReportClass($customReportUpdate->getReportClass());
        $config->setChartType($customReportUpdate->getChartType());
        $config->setPieColumn($customReportUpdate->getPieColumn());
        $config->setPieLabelColumn($customReportUpdate->getPieLabelColumn());
        $config->setXAxis($customReportUpdate->getXAxis());
        $config->setYAxis($customReportUpdate->getYAxis());
        $config->setShareGlobally($customReportUpdate->getSharedGlobally());
        $config->setSharedUserNames($customReportUpdate->getSharedUserNames());
        $config->setSharedRoleNames($customReportUpdate->getSharedRoleNames());
        $config->setPagination($adapter->getPagination());

        return $config;
    }
}
