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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Hydrator;

use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportChartData;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportDetails;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportDrillDownOption;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportTreeConfigNode;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportTreeNode;

/**
 * @internal
 */
interface CustomReportHydratorInterface
{
    public function extractReportDetails(Config $report): CustomReportDetails;

    public function extractConfigTreeData(Config $report): CustomReportTreeConfigNode;

    public function extractTreeData(Config $report): CustomReportTreeNode;

    public function extractChartData(array $chartData): CustomReportChartData;

    public function hydrateDrillDownOption(array $drillDownData): CustomReportDrillDownOption;
}
