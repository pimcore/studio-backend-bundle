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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Repository;

use Carbon\Carbon;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Schedule\TaskResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotAuthorizedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Request\UpdateElementSchedules;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Schema\UpdateSchedule;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ScheduleActions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Schedule\Task;
use function in_array;
use function sprintf;

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
    ) {
    }

    /**
     * @throws NotAuthorizedException
     */
    public function createSchedule(string $elementType, int $id): Task
    {
        $this->checkElementPermissions($elementType, $id);

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
     * @throws NotFoundException
     */
    public function getSchedule(int $id): Task
    {
        $task = $this->taskResolver->getById($id);

        if (!$task) {
            throw new NotFoundException('Task', $id);
        }

        $this->checkElementPermissions($task->getCtype(), $task->getCid());

        return $task;
    }

    /**
     * @return array<int, Task>
     *
     * @throws NotFoundException
     */
    public function listSchedules(string $elementType, int $id): array
    {
        return $this->checkElementPermissions($elementType, $id)->getScheduledTasks();
    }

    /**
     * @throws DatabaseException|InvalidArgumentException|ForbiddenException
     */
    public function updateSchedules(
        string $elementType,
        int $id,
        UpdateElementSchedules $updateElementSchedules
    ): void {
        $this->checkElementPermissions($elementType, $id);

        $schedules = $updateElementSchedules->getSchedules();
        $currentUserId = $this->securityService->getCurrentUser()->getId();

        // Everything is checked before the first write, so a rejected request leaves no partial
        // changes behind: every action has to be valid and every submitted task has to belong to
        // this element already.
        $existingTasks = [];
        foreach ($schedules as $index => $schedule) {
            $this->validateAction($elementType, $schedule->getAction());

            if (!$schedule->getId()) {
                continue;
            }

            $task = $this->taskResolver->getById($schedule->getId());
            if ($task && ($task->getCid() !== $id || $task->getCtype() !== $elementType)) {
                throw new ForbiddenException('You are not allowed to update this schedule');
            }

            $existingTasks[$index] = $task;
        }

        $currentTasks = [];
        foreach ($schedules as $index => $schedule) {
            $isNew = !$schedule->getId();
            $task = $isNew ? $this->createSchedule($elementType, $id) : $existingTasks[$index];

            if (!$task) {
                continue;
            }

            // A task is executed with the permissions of its owner. Whoever creates or changes a
            // task becomes its owner; a task that is submitted unchanged keeps the user it was
            // scheduled by.
            if ($isNew || $this->isScheduleChanged($task, $schedule)) {
                $task->setUserId($currentUserId);
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

        $this->deleteObsoleteTasks($currentTasks, $id, $elementType);
    }

    private function isScheduleChanged(Task $task, UpdateSchedule $schedule): bool
    {
        return $task->getDate() !== $schedule->getDate()
            || $task->getAction() !== $schedule->getAction()
            || $task->getVersion() !== $schedule->getVersion()
            || $task->getActive() !== $schedule->isActive();
    }

    public function deleteSchedule(int $id): void
    {
        $task = $this->getSchedule($id);

        $this->checkElementPermissions($task->getCtype(), $task->getCid());

        $queryBuilder = $this->dbResolver->get()->createQueryBuilder();

        $queryBuilder->delete('schedule_tasks')
            ->where('id = :id')
            ->setParameter('id', $task->getId());

        try {
            $queryBuilder->executeStatement();
        } catch (Exception) {
            throw new DatabaseException();
        }
    }

    /**
     * @throws DatabaseException
     */
    private function deleteObsoleteTasks(array $currentTasks, int $cid, string $ctype): void
    {
        $queryBuilder = $this->dbResolver->get()->createQueryBuilder();

        $queryBuilder->delete('schedule_tasks')
            ->where('id NOT IN (:ids) AND cid = :cid AND ctype = :ctype')
            ->setParameter('ids', $currentTasks, ArrayParameterType::INTEGER)
            ->setParameter('cid', $cid)
            ->setParameter('ctype', $ctype);

        try {
            $queryBuilder->executeStatement();
        } catch (Exception) {
            throw new DatabaseException();
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateAction(string $elementType, ?string $action): void
    {
        if ($action === null) {
            return;
        }

        $allowedActions = ScheduleActions::forElementType($elementType);
        $allowedValues = array_map(
            static fn (ScheduleActions $a): string => $a->value,
            $allowedActions
        );

        if (!in_array($action, $allowedValues, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Action "%s" is not supported for element type "%s". Allowed actions: %s',
                    $action,
                    $elementType,
                    implode(', ', $allowedValues)
                )
            );
        }
    }

    private function checkElementPermissions(string $elementType, int $id): ElementInterface
    {
        $element = $this->getElement($this->serviceResolver, $elementType, $id);

        $this->securityService->hasElementPermissions(
            $element,
            $this->securityService->getCurrentUser(),
            ['settings', 'versions']
        );

        return $element;
    }
}
