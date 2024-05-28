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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Event\ScheduleEvent;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Hydrator\ScheduleHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Repository\ScheduleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Request\UpdateElementSchedules;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Schema\Schedule;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class ScheduleService implements ScheduleServiceInterface
{
    public function __construct(
        private ScheduleRepositoryInterface $scheduleRepository,
        private ScheduleHydratorInterface $scheduleHydrator,
        private EventDispatcherInterface $eventDispatcher
    )
    {
    }

    public function createSchedule(string $elementType, int $id): Schedule
    {
        $task = $this->scheduleRepository->createSchedule($elementType, $id);

        $schedule = $this->scheduleHydrator->hydrate($task);

        $this->eventDispatcher->dispatch(
            new ScheduleEvent($schedule),
            ScheduleEvent::EVENT_NAME
        );

        return $schedule;
    }

    /**
     * @return array<int, Schedule>
     */
    public function listSchedules(string $elementType, int $id): array
    {
        $tasks = $this->scheduleRepository->listSchedules($elementType, $id);

        $schedules = [];

        foreach ($tasks as $task) {
            $schedule = $this->scheduleHydrator->hydrate($task);

            $this->eventDispatcher->dispatch(
                new ScheduleEvent($schedule),
                ScheduleEvent::EVENT_NAME
            );

            $schedules[] = $schedule;
        }

        return $schedules;
    }

    /**
     * @throws DatabaseException
     */
    public function updateSchedules(
        string $elementType,
        int $id,
        UpdateElementSchedules $updateElementSchedules
    ): void
    {
       $this->scheduleRepository->updateSchedules($elementType, $id, $updateElementSchedules);
    }

    public function deleteSchedule(int $id): void
    {
        $this->scheduleRepository->deleteSchedule($id);
    }
}
