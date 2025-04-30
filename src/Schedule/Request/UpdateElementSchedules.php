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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Request;

use Pimcore\Bundle\StudioBackendBundle\Schedule\Schema\UpdateSchedule;

/**
 * @internal
 */
final readonly class UpdateElementSchedules
{
    /** @var array<int, UpdateSchedule> */
    private array $schedules;

    public function __construct(
        array $items
    ) {
        $this->schedules = array_map(static function (array $scheduleData) {
            return new UpdateSchedule(
                $scheduleData['id'],
                $scheduleData['date'],
                $scheduleData['action'],
                $scheduleData['version'],
                $scheduleData['active'],
            );
        }, $items);
    }

    /**
     * @return array<int, UpdateSchedule>
     */
    public function getSchedules(): array
    {
        return $this->schedules;
    }
}
