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
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportTreeNodeFolder;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportUpdate;

/**
 * @internal
 */
interface CustomReportHydratorInterface
{
    public function extractReportDetails(Config $report): CustomReportDetails;

    public function extractConfigTreeData(Config $report): CustomReportTreeConfigNode;

    public function extractTreeData(Config $report): CustomReportTreeNode;

    /**
     * @param CustomReportTreeConfigNode[] $children
     */
    public function extractTreeFolderData(
        string $group,
        string $groupIconClass,
        array $children
    ): CustomReportTreeNodeFolder;

    public function extractChartData(array $chartData): CustomReportChartData;

    public function hydrateDrillDownOption(array $drillDownData): CustomReportDrillDownOption;

    public function dehydrateReportDetails(
        Config $config,
        CustomReportUpdate $customReportUpdate
    ): Config;
}
