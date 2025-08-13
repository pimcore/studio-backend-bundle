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

use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportColumnInformation;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class ColumnInformationEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.custom_report.column_information';

    public function __construct(
        private readonly CustomReportColumnInformation $columnInformation
    ) {
        parent::__construct($this->columnInformation);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getColumnInformation(): CustomReportColumnInformation
    {
        return $this->columnInformation;
    }
}
