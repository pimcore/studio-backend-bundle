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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Event;

use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportDrillDownOption;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class DrillDownOptionEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.custom_report.drill_down_option';

    public function __construct(
        private readonly CustomReportDrillDownOption $option
    ) {
        parent::__construct($option);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getDrillDownOption(): CustomReportDrillDownOption
    {
        return $this->option;
    }
}
