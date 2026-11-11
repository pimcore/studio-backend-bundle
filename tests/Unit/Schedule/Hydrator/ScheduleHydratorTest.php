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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Schedule\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Hydrator\ScheduleHydrator;
use Pimcore\Bundle\StudioBackendBundle\User\Resolver\UserAccountResolverInterface;
use Pimcore\Model\Schedule\Task;
use Pimcore\Model\User;
use Pimcore\Model\User\ServiceUser;

/**
 * @internal
 */
final class ScheduleHydratorTest extends Unit
{
    public function testScheduleOfAServiceUserCarriesItsName(): void
    {
        $serviceUser = new ServiceUser();
        $serviceUser->setId(7);
        $serviceUser->setName('svc-erp-sync');

        $schedule = $this->hydrator([7 => $serviceUser])->hydrate($this->task(7));

        $this->assertSame(7, $schedule->getUserId());
        $this->assertSame('svc-erp-sync', $schedule->getUsername());
    }

    public function testScheduleOfAHumanCarriesItsName(): void
    {
        $human = new User();
        $human->setId(1);
        $human->setName('jane');

        $this->assertSame('jane', $this->hydrator([1 => $human])->hydrate($this->task(1))->getUsername());
    }

    /**
     * @param array<int, User> $users
     */
    private function hydrator(array $users): ScheduleHydrator
    {
        return new ScheduleHydrator(
            $this->makeEmpty(UserAccountResolverInterface::class, [
                'getAccountById' => static fn (int $id): ?User => $users[$id] ?? null,
            ])
        );
    }

    private function task(int $userId): Task
    {
        $task = new Task();
        $task->setId(11);
        $task->setCtype('document');
        $task->setDate(1700000000);
        $task->setAction('publish');
        $task->setActive(true);
        $task->setUserId($userId);

        return $task;
    }
}
