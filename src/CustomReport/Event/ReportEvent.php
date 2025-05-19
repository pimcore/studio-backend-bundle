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

use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportDetails;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class ReportEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.custom_report_report';

    public function __construct(
        private readonly CustomReportDetails $report
    ) {
        parent::__construct($this->report);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getReport(): CustomReportDetails
    {
        return $this->report;
    }
}
