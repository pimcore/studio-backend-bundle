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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Event;

use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportChartData;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class ChartDataEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.custom_report_chart_data';

    public function __construct(
        private readonly CustomReportChartData $chartData
    ) {
        parent::__construct($this->chartData);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getReport(): CustomReportChartData
    {
        return $this->chartData;
    }
}
