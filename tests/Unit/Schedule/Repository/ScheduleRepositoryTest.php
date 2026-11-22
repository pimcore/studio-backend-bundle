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

    public function testUpdateSchedulesResetsTheOwnerWhenEditingAnExistingTaskOfTheSameElement(): void
    {
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();

        $task = $this->makeEmpty(Task::class, [
            'getId' => 3,
            'getCid' => 7,
            'getCtype' => ElementTypes::TYPE_DOCUMENT,
            'setUserId' => Expected::once(function (?int $userId): void {
                $this->assertSame(self::CURRENT_USER_ID, $userId);
            }),
            'save' => Expected::once(),
        ]);

        $repository = $this->createRepository($task);

        $repository->updateSchedules(
            ElementTypes::TYPE_DOCUMENT,
            7,
            $this->schedulesPayload(3, 'publish')
        );
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

    private function createRepository(Task $resolvedTask): ScheduleRepository
    {
        $element = $this->makeEmpty(Document::class, ['getId' => 7]);

        $serviceResolver = $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => $element,
        ]);

        $taskResolver = $this->makeEmpty(TaskResolverInterface::class, [
            'getById' => $resolvedTask,
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
