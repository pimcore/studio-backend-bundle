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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\KeyBindingHydrator;

/**
 * @internal
 */
final class KeyBindingHydratorTest extends Unit
{
    public function testHydrate(): void
    {
        $hydrator = new KeyBindingHydrator();

        $data = [
            [
                'key' => 65,
                'action' => 'test',
                'ctrl' => true,
                'alt' => false,
                'shift' => true,
            ],
            [
                'key' => 66,
                'action' => 'test2',
                'ctrl' => false,
                'alt' => true,
                'shift' => false,
            ],
        ];

        $keyBindings = $hydrator->hydrate($data);

        $this->assertCount(2, $keyBindings);
        $this->assertSame(65, $keyBindings[0]->getKey());
        $this->assertSame('test', $keyBindings[0]->getAction());
        $this->assertTrue($keyBindings[0]->getCtrl());
        $this->assertFalse($keyBindings[0]->getAlt());
        $this->assertTrue($keyBindings[0]->getShift());
    }
}
