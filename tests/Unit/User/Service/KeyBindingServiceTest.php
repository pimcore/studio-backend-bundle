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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\KeyBindingHydrator;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\KeyBinding;
use Pimcore\Bundle\StudioBackendBundle\User\Service\KeyBindingService;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(KeyBindingService::class)]
#[UsesClass(KeyBindingHydrator::class)]
#[UsesClass(KeyBinding::class)]
final class KeyBindingServiceTest extends TestCase
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
        $keyBindingService = new KeyBindingService(
            $data,
            $keyBindingHydrator,
            $this->createMock(LoggerInterface::class)
        );

        $keyBindings = $keyBindingService->getDefaultKeyBindings();
        $this->assertCount(2, $keyBindings);
        $this->assertSame(76, $keyBindings[0]->getKey());
        $this->assertSame('test', $keyBindings[0]->getAction());
        $this->assertTrue($keyBindings[0]->getCtrl());
        $this->assertFalse($keyBindings[0]->getAlt());
        $this->assertTrue($keyBindings[0]->getShift());
    }
}
