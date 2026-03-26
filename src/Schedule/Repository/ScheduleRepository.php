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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotAuthorizedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Request\UpdateElementSchedules;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ScheduleActions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Element\ElementInterface;
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

    public function updateSchedules(
        string $elementType,
        int $id,
        UpdateElementSchedules $updateElementSchedules
    ): void {
        $this->checkElementPermissions($elementType, $id);

        $schedules = $updateElementSchedules->getSchedules();

        $currentTasks = [];
        foreach ($schedules as $schedule) {

            if ($schedule->getId()) {
                $task = $this->taskResolver->getById($schedule->getId());
            } else {
                $task = $this->createSchedule($elementType, $id);
            }

            if (!$task) {
                continue;
            }

            $currentTasks[] = $task->getId();
            $task->setCid($id);
            $task->setCtype($elementType);
            $task->setDate($schedule->getDate());
            $this->validateAction($elementType, $schedule->getAction());
            $task->setAction($schedule->getAction());
            $task->setVersion($schedule->getVersion());
            $task->setActive($schedule->isActive());
            $task->save();
        }

        $this->deleteObsoleteTasks($currentTasks, $id);
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
