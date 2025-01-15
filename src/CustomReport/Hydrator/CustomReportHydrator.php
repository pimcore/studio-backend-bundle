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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Hydrator;

use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportChartData;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportDetails;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportTreeConfigNode;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportTreeNode;

/**
 * @internal
 */
final readonly class CustomReportHydrator implements CustomReportHydratorInterface
{
    public function extractTreeData(Config $report): CustomReportTreeNode
    {
        return new CustomReportTreeNode(
            htmlspecialchars($report->getName()),
            htmlspecialchars($report->getNiceName()),
            htmlspecialchars($report->getIconClass()),
            htmlspecialchars($report->getGroup()),
            htmlspecialchars($report->getGroupIconClass()),
            $report->getMenuShortcut(),
            htmlspecialchars($report->getReportClass())
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
            $report->getDataSourceConfig(),
            $this->getCustomReportColumnConfiguration(
                $report->getColumnConfiguration()
            ),
            $report->getNiceName(),
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
            $report->getPieColumn(),
            $report->getPieLabelColumn(),
            $report->getXAxis(),
            $report->getYAxis(),
        );
    }

    public function extractChartData(array $chartData): CustomReportChartData
    {
        return new CustomReportChartData(
            $chartData['data'] ?? []
        );
    }

    private function getCustomReportColumnConfiguration(array $columns): array
    {
        $columnConfig = [];
        foreach ($columns as $column) {
            $columnConfig[] = new CustomReportColumnConfiguration(
                $column['name'] ?? '',
                $column['display'] ?? '',
                $column['export'] ?? '',
                $column['order'] ?? '',
                $column['label'] ?? '',
                $column['id'] ?? ''
            );
        }

        return $columnConfig;
    }
}
