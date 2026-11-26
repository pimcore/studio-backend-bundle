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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\MaintenanceMode\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Hydrator\MaintenanceModeStateHydrator;

/**
 * @internal
 */
final class MaintenanceModeStateHydratorTest extends Unit
{
    public function testHydrateMapsAnActiveMode(): void
    {
        $state = (new MaintenanceModeStateHydrator())->hydrate(true);

        $this->assertTrue($state->isActive());
    }

    public function testHydrateMapsAnInactiveMode(): void
    {
        $state = (new MaintenanceModeStateHydrator())->hydrate(false);

        $this->assertFalse($state->isActive());
        $this->assertSame([], $state->getAdditionalAttributes());
    }
}
