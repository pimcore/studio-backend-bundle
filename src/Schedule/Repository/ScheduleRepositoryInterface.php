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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotAuthorizedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Request\UpdateElementSchedules;
use Pimcore\Model\Schedule\Task;

/**
 * @internal
 */
interface ScheduleRepositoryInterface
{
    /**
     * @throws NotAuthorizedException
     */
    public function createSchedule(string $elementType, int $id): Task;

    /**
     * @throws NotFoundException
     */
    public function getSchedule(int $id): Task;

    public function listSchedules(string $elementType, int $id): array;

    /**
     * @throws DatabaseException
     */
    public function updateSchedules(
        string $elementType,
        int $id,
        UpdateElementSchedules $updateElementSchedules
    ): void;

    /**
     * @throws NotFoundException|DatabaseException
     */
    public function deleteSchedule(int $id): void;
}
