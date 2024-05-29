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

use Pimcore\Bundle\StudioBackendBundle\Exception\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\NotAuthorizedException;
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
     * @throws ElementNotFoundException
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
     * @throws ElementNotFoundException|DatabaseException
     */
    public function deleteSchedule(int $id): void;
}
