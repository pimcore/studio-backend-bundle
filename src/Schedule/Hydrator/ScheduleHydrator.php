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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Hydrator;

use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Schema\Schedule;
use Pimcore\Model\Schedule\Task;

/**
 * @internal
 */
final readonly class ScheduleHydrator implements ScheduleHydratorInterface
{
    public function __construct(private UserResolverInterface $userResolver)
    {
    }

    public function hydrate(Task $task): Schedule
    {
        $user = $this->userResolver->getById($task->getUserId());

        return new Schedule(
            $task->getId(),
            $task->getCtype(),
            $task->getDate(),
            $this->matchAction($task->getAction()),
            $task->getVersion(),
            $task->getActive(),
            $task->getUserId(),
            $user?->getUsername()
        );
    }

    private function matchAction(?string $action): ?string
    {
        return match($action) {
            'publish-version' => 'publish',
            default => $action,
        };
    }
}
