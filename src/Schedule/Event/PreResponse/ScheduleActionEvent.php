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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Schema\ScheduleAction;

final class ScheduleActionEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.schedule.action_type';

    public function __construct(
        private readonly ScheduleAction $scheduleAction
    ) {
        parent::__construct($this->scheduleAction);
    }

    public function getScheduleAction(): ScheduleAction
    {
        return $this->scheduleAction;
    }
}
