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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Hydrator;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\KeyBindingHydrator;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\KeyBinding;

/**
 * @internal
 */
#[CoversClass(KeyBindingHydrator::class)]
#[UsesClass(KeyBinding::class)]
final class KeyBindingHydratorTest extends TestCase
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
