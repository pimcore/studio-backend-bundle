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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\KeyBindingHydrator;
use Pimcore\Bundle\StudioBackendBundle\User\Service\KeyBindingService;

/**
 * @internal
 */
final class KeyBindingServiceTest extends Unit
{
    public function testGetDefaultKeyBindings(): void
    {
        $data = [
            [
                'key' => 'L',
                'action' => 'test',
                'ctrl' => true,
                'alt' => false,
                'shift' => true,
            ],
            [
                'key' => 'P',
                'action' => 'test2',
                'ctrl' => false,
                'alt' => true,
                'shift' => false,
            ],
        ];

        $keyBindingHydrator = new KeyBindingHydrator();
        $keyBindingService = new KeyBindingService($data, $keyBindingHydrator);

        $keyBindings = $keyBindingService->getDefaultKeyBindings();
        $this->assertCount(2, $keyBindings);
        $this->assertSame(76, $keyBindings[0]->getKey());
        $this->assertSame('test', $keyBindings[0]->getAction());
        $this->assertTrue($keyBindings[0]->getCtrl());
        $this->assertFalse($keyBindings[0]->getAlt());
        $this->assertTrue($keyBindings[0]->getShift());
    }
}
