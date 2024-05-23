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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Schedule\Schema\Schedule;
use Pimcore\Model\Schedule\Task;

/**
 * @internal
 */
final class ScheduleHydrator implements ScheduleHydratorInterface
{
    public function hydrate(Task $task): Schedule
    {
        return new Schedule(
            $task->getId(),
            $task->getCtype(),
            $task->getDate(),
            $task->getAction(),
            $task->getVersion(),
            $task->getActive(),
            $task->getUserId()
        );
    }
}
