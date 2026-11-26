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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Schedule\Request;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Schedule\Request\UpdateElementSchedules;

/**
 * @internal
 */
final class UpdateElementSchedulesTest extends Unit
{
    public function testOptionalKeysAreNullWhenTheClientOmitsThem(): void
    {
        // The UI drops keys whose value is undefined, e.g. a new row without action and version.
        $request = new UpdateElementSchedules([
            ['id' => null, 'date' => 1893456000, 'active' => true],
        ]);

        $schedule = $request->getSchedules()[0];

        $this->assertNull($schedule->getId());
        $this->assertNull($schedule->getAction());
        $this->assertNull($schedule->getVersion());
        $this->assertSame(1893456000, $schedule->getDate());
        $this->assertTrue($schedule->isActive());
    }

    public function testVersionMayBeOmittedForActionsWithoutAVersion(): void
    {
        $request = new UpdateElementSchedules([
            ['id' => null, 'date' => 1893456000, 'action' => 'publish', 'active' => false],
        ]);

        $schedule = $request->getSchedules()[0];

        $this->assertSame('publish', $schedule->getAction());
        $this->assertNull($schedule->getVersion());
        $this->assertFalse($schedule->isActive());
    }

    public function testIdMayBeOmittedForANewSchedule(): void
    {
        $request = new UpdateElementSchedules([
            ['date' => 1893456000, 'action' => 'publish', 'active' => true],
        ]);

        $this->assertNull($request->getSchedules()[0]->getId());
    }

    public function testSubmittedValuesAreKept(): void
    {
        $request = new UpdateElementSchedules([
            ['id' => 7, 'date' => 1893456000, 'action' => 'publish-version', 'version' => 5, 'active' => true],
        ]);

        $schedule = $request->getSchedules()[0];

        $this->assertSame(7, $schedule->getId());
        $this->assertSame('publish-version', $schedule->getAction());
        $this->assertSame(5, $schedule->getVersion());
    }
}
