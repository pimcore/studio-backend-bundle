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

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Request;

use Pimcore\Bundle\StudioBackendBundle\Schedule\Schema\UpdateSchedule;

/**
 * @internal
 */
final readonly class UpdateElementSchedules
{
    /** @var array<int, UpdateSchedule>  */
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
