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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Schedule\Repository;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Schedule\TaskResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Repository\ScheduleRepository;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Request\UpdateElementSchedules;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Document;
use Pimcore\Model\Schedule\Task;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ScheduleRepositoryTest extends Unit
{
    private const int CURRENT_USER_ID = 2;

    private MockObject|DbResolverInterface $dbResolver;

    private MockObject|Connection $connection;

    private MockObject|QueryBuilder $queryBuilder;

    protected function _before(): void
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->queryBuilder->method('delete')->willReturnSelf();

        $this->connection = $this->createMock(Connection::class);
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilder);

        $this->dbResolver = $this->createMock(DbResolverInterface::class);
        $this->dbResolver->method('get')->willReturn($this->connection);
    }

    public function testUpdateSchedulesRejectsATaskBelongingToAnotherElement(): void
    {
        // Task 3 is owned by document 4, the caller is only allowed on document 7.
        $task = $this->makeEmpty(Task::class, [
            'getId' => 3,
            'getCid' => 4,
            'getCtype' => ElementTypes::TYPE_DOCUMENT,
            'save' => Expected::never(),
        ]);

        $repository = $this->createRepository($task);

        $this->expectException(ForbiddenException::class);

        $repository->updateSchedules(
            ElementTypes::TYPE_DOCUMENT,
            7,
            $this->schedulesPayload(3, 'publish')
        );
    }

    public function testUpdateSchedulesRejectsATaskBelongingToAnElementOfAnotherType(): void
    {
        // Same id (7), but the task actually belongs to a data object, not the document.
        $task = $this->makeEmpty(Task::class, [
            'getId' => 1,
            'getCid' => 7,
            'getCtype' => ElementTypes::TYPE_OBJECT,
            'save' => Expected::never(),
        ]);

        $repository = $this->createRepository($task);

        $this->expectException(ForbiddenException::class);

        $repository->updateSchedules(
            ElementTypes::TYPE_DOCUMENT,
            7,
            $this->schedulesPayload(1, 'publish')
        );
    }

    public function testUpdateSchedulesMakesTheEditorTheOwnerOfAChangedTask(): void
    {
        // the stored task has another date than the submitted one, so it counts as changed
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();

        $assignedUserId = null;
        $task = null;
        $task = $this->makeEmpty(Task::class, [
            'getId' => 3,
            'getCid' => 7,
            'getCtype' => ElementTypes::TYPE_DOCUMENT,
            'getDate' => 1500000000,
            'getAction' => 'publish',
            'getActive' => true,
            // setUserId() is fluent (returns static), so the stub has to hand back the task itself
            'setUserId' => Expected::once(function (?int $userId) use (&$assignedUserId, &$task): Task {
                $assignedUserId = $userId;

                return $task;
            }),
            'save' => Expected::once(),
        ]);

        $repository = $this->createRepository($task);

        $repository->updateSchedules(
            ElementTypes::TYPE_DOCUMENT,
            7,
            $this->schedulesPayload(3, 'publish')
        );

        $this->assertSame(self::CURRENT_USER_ID, $assignedUserId);
    }

    public function testUpdateSchedulesKeepsTheOwnerOfAnUnchangedTask(): void
    {
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();

        // submitted exactly as stored: the task keeps running with the rights of the user who scheduled it
        $task = $this->makeEmpty(Task::class, [
            'getId' => 3,
            'getCid' => 7,
            'getCtype' => ElementTypes::TYPE_DOCUMENT,
            'getDate' => 1600000000,
            'getAction' => 'publish',
            'getVersion' => null,
            'getActive' => true,
            'setUserId' => Expected::never(),
            'save' => Expected::once(),
        ]);

        $this->createRepository($task)->updateSchedules(
            ElementTypes::TYPE_DOCUMENT,
            7,
            $this->schedulesPayload(3, 'publish')
        );
    }

    /**
     * @dataProvider changedScheduleProvider
     */
    public function testUpdateSchedulesMakesTheEditorTheOwnerWhenAnyValueChanges(array $stored): void
    {
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();

        $assignedUserId = null;
        $task = null;
        $task = $this->makeEmpty(Task::class, $stored + [
            'getId' => 3,
            'getCid' => 7,
            'getCtype' => ElementTypes::TYPE_DOCUMENT,
            'getDate' => 1600000000,
            'getAction' => 'publish',
            'getVersion' => null,
            'getActive' => true,
            'setUserId' => function (?int $userId) use (&$assignedUserId, &$task): Task {
                $assignedUserId = $userId;

                return $task;
            },
        ]);

        $this->createRepository($task)->updateSchedules(
            ElementTypes::TYPE_DOCUMENT,
            7,
            $this->schedulesPayload(3, 'publish')
        );

        $this->assertSame(self::CURRENT_USER_ID, $assignedUserId);
    }

    /**
     * The stored value that differs from the submitted schedule
     * (date 1600000000, action "publish", no version, active).
     *
     * @return array<string, array{array<string, mixed>}>
     */
    public function changedScheduleProvider(): array
    {
        return [
            'date' => [['getDate' => 1700000000]],
            'action' => [['getAction' => 'unpublish']],
            'version' => [['getVersion' => 12]],
            're-activated' => [['getActive' => false]],
        ];
    }

    public function testARejectedTaskLeavesTheSchedulesSubmittedBeforeItUnwritten(): void
    {
        // Task 5 belongs to the element and is submitted first, task 3 belongs to another element.
        $ownTask = $this->makeEmpty(Task::class, [
            'getId' => 5,
            'getCid' => 7,
            'getCtype' => ElementTypes::TYPE_DOCUMENT,
            'setUserId' => Expected::never(),
            'save' => Expected::never(),
        ]);
        $foreignTask = $this->makeEmpty(Task::class, [
            'getId' => 3,
            'getCid' => 4,
            'getCtype' => ElementTypes::TYPE_DOCUMENT,
            'save' => Expected::never(),
        ]);
        $this->queryBuilder->expects($this->never())->method('executeStatement');

        $repository = $this->createRepository([5 => $ownTask, 3 => $foreignTask]);

        $this->expectException(ForbiddenException::class);

        $repository->updateSchedules(ElementTypes::TYPE_DOCUMENT, 7, new UpdateElementSchedules([
            ['id' => 5, 'date' => 1900000000, 'action' => 'unpublish', 'version' => null, 'active' => true],
            ['id' => 3, 'date' => 1600000000, 'action' => 'publish', 'version' => null, 'active' => true],
        ]));
    }

    public function testAnInvalidActionLeavesTheSchedulesSubmittedBeforeItUnwritten(): void
    {
        $ownTask = $this->makeEmpty(Task::class, [
            'getId' => 5,
            'getCid' => 7,
            'getCtype' => ElementTypes::TYPE_DOCUMENT,
            'save' => Expected::never(),
        ]);
        $this->queryBuilder->expects($this->never())->method('executeStatement');

        $repository = $this->createRepository([5 => $ownTask]);

        $this->expectException(InvalidArgumentException::class);

        $repository->updateSchedules(ElementTypes::TYPE_DOCUMENT, 7, new UpdateElementSchedules([
            ['id' => 5, 'date' => 1900000000, 'action' => 'unpublish', 'version' => null, 'active' => true],
            ['id' => 5, 'date' => 1900000000, 'action' => 'not-an-action', 'version' => null, 'active' => true],
        ]));
    }

    /**
     * @dataProvider unknownTaskIdProvider
     */
    public function testAnUnknownTaskIdIsRejectedWithoutWritingOrDeleting(int $unknownId): void
    {
        // Task 5 belongs to the element, the other id does not exist (anymore). Skipping it, or
        // treating it as a new schedule, would leave it out of the kept ids, so the obsolete-task
        // cleanup would delete stored tasks the caller never meant to remove. Only null means "new".
        $ownTask = $this->makeEmpty(Task::class, [
            'getId' => 5,
            'getCid' => 7,
            'getCtype' => ElementTypes::TYPE_DOCUMENT,
            'save' => Expected::never(),
        ]);
        $this->queryBuilder->expects($this->never())->method('executeStatement');

        $repository = $this->createRepository([5 => $ownTask]);

        $this->expectException(NotFoundException::class);

        $repository->updateSchedules(ElementTypes::TYPE_DOCUMENT, 7, new UpdateElementSchedules([
            ['id' => 5, 'date' => 1900000000, 'action' => 'unpublish', 'version' => null, 'active' => true],
            ['id' => $unknownId, 'date' => 1600000000, 'action' => 'publish', 'version' => null, 'active' => true],
        ]));
    }

    /**
     * @return array<string, array{int}>
     */
    public function unknownTaskIdProvider(): array
    {
        return [
            'deleted task' => [99],
            'zero' => [0],
            'negative draft id' => [-1764081234000],
        ];
    }

    public function testDeleteObsoleteTasksOnlyRemovesTasksOfTheSameElementType(): void
    {
        $task = $this->makeEmpty(Task::class, [
            'getId' => 3,
            'getCid' => 7,
            'getCtype' => ElementTypes::TYPE_DOCUMENT,
            'save' => Expected::once(),
        ]);

        $parameters = [];
        $this->queryBuilder->method('setParameter')->willReturnCallback(
            function (string $name, mixed $value) use (&$parameters) {
                $parameters[$name] = $value;

                return $this->queryBuilder;
            }
        );
        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with($this->stringContains('ctype = :ctype'))
            ->willReturnSelf();

        $repository = $this->createRepository($task);

        $repository->updateSchedules(
            ElementTypes::TYPE_DOCUMENT,
            7,
            $this->schedulesPayload(3, 'publish')
        );

        $this->assertSame(7, $parameters['cid']);
        $this->assertSame(ElementTypes::TYPE_DOCUMENT, $parameters['ctype']);
    }

    private function schedulesPayload(int $taskId, string $action): UpdateElementSchedules
    {
        return new UpdateElementSchedules([
            ['id' => $taskId, 'date' => 1600000000, 'action' => $action, 'version' => null, 'active' => true],
        ]);
    }

    /**
     * @param Task|array<int, Task> $resolvedTasks the task every id resolves to, or the tasks by id
     */
    private function createRepository(Task|array $resolvedTasks): ScheduleRepository
    {
        $element = $this->makeEmpty(Document::class, ['getId' => 7]);

        $serviceResolver = $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => $element,
        ]);

        $taskResolver = $this->makeEmpty(TaskResolverInterface::class, [
            'getById' => static fn (int $id): ?Task => $resolvedTasks instanceof Task
                ? $resolvedTasks
                : ($resolvedTasks[$id] ?? null),
        ]);

        $currentUser = $this->makeEmpty(UserInterface::class, [
            'getId' => self::CURRENT_USER_ID,
        ]);

        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $currentUser,
            'hasElementPermissions' => null,
        ]);

        return new ScheduleRepository(
            $serviceResolver,
            $this->dbResolver,
            $taskResolver,
            $securityService
        );
    }
}
