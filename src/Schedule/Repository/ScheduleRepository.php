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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Repository;

use Carbon\Carbon;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Schedule\TaskResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Request\UpdateElementSchedules;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Schedule\Task;

/**
 * @internal
 */
final readonly class ScheduleRepository implements ScheduleRepositoryInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ServiceResolverInterface $serviceResolver,
        private DbResolverInterface $dbResolver,
        private TaskResolverInterface $taskResolver,
        private SecurityServiceInterface $securityService,
    )
    {
    }

    public function createSchedule(string $elementType, int $id): Task
    {
        $user = $this->securityService->getCurrentUser();

        $task = new Task();
        $task->setCtype($elementType);
        $task->setCid($id);
        $task->setDate(Carbon::today()->getTimestamp());
        $task->setActive(true);
        $task->setUserId($user->getId());
        $task->save();

        return $task;
    }

    /**
     * @return array<int, Task>
     * @throws ElementNotFoundException
     */
    public function listSchedules(string $elementType, int $id): array
    {
        return $this->getElement($this->serviceResolver, $elementType, $id)->getScheduledTasks();
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
        $schedules = $updateElementSchedules->getSchedules();

        $currentTasks = [];
        foreach ($schedules as $schedule) {
            $task = $this->taskResolver->getById($schedule->getId());

            if(!$task) {
                continue;
            }

            $currentTasks[] = $task->getId();
            $task->setCid($id);
            $task->setCtype($elementType);
            $task->setDate($schedule->getDate());
            $task->setAction($schedule->getAction());
            $task->setVersion($schedule->getVersion());
            $task->setActive($schedule->isActive());
            $task->save();
        }


        $this->deleteObsoleteTasks($currentTasks, $id);
    }

    /**
     * @throws ElementNotFoundException|DatabaseException
     */
    public function delete(int $id): void
    {
        $task = $this->taskResolver->getById($id);

        if (!$task) {
            throw new ElementNotFoundException( $id, 'Task');
        }

        $queryBuilder = $this->dbResolver->get()->createQueryBuilder();

        $queryBuilder->delete('schedule_tasks')
            ->where('id = :id')
            ->setParameter('id', $id);

        try {
            $queryBuilder->executeStatement();
        } catch (Exception) {
            throw new DatabaseException();
        }
    }

    /**
     * @throws DatabaseException
     */
    private function deleteObsoleteTasks(array $currentTasks, int $cid): void
    {
        $queryBuilder = $this->dbResolver->get()->createQueryBuilder();

        $queryBuilder->delete('schedule_tasks')
            ->where('id NOT IN (:ids) AND cid = :cid')
            ->setParameter('ids', $currentTasks, ArrayParameterType::INTEGER)
            ->setParameter('cid', $cid);

        try {
            $queryBuilder->executeStatement();
        } catch (Exception) {
            throw new DatabaseException();
        }
    }
}
