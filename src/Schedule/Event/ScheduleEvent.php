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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Schema\Schedule;

final class ScheduleEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.schedule';

    public function __construct(
        private readonly Schedule $schedule
    ) {
        parent::__construct($schedule);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getSchedule(): Schedule
    {
        return $this->schedule;
    }
}
