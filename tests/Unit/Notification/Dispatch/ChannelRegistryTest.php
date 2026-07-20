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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidNotificationChannelException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistry;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestChannel;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestNotificationTypeDescriptor;

final class ChannelRegistryTest extends Unit
{
    public function testAvailableChannelsAlwaysLeadWithPopupAndFollowSortOrder(): void
    {
        $registry = new ChannelRegistry(
            [new TestChannel('teams', sortOrder: 200), new TestChannel('email', sortOrder: 100)],
            []
        );

        $this->assertSame(
            [ChannelRegistryInterface::POPUP_CHANNEL, 'email', 'teams'],
            $registry->getAvailableChannelIds()
        );
    }

    /**
     * The whole point of the capability model: a type says only whether it may leave the
     * application, never through which channel. A channel contributed later therefore lights
     * up for existing types without those bundles being touched.
     */
    public function testSupportedChannelsAreDerivedFromCapabilityNotEnumeration(): void
    {
        $registry = new ChannelRegistry([new TestChannel('email'), new TestChannel('teams')], []);

        $external = new TestNotificationTypeDescriptor('external.type', allowsExternalDelivery: true);
        $internal = new TestNotificationTypeDescriptor('internal.type', allowsExternalDelivery: false);

        $this->assertSame(
            [ChannelRegistryInterface::POPUP_CHANNEL, 'email', 'teams'],
            $registry->getSupportedChannelIds($external)
        );

        $this->assertSame(
            [ChannelRegistryInterface::POPUP_CHANNEL],
            $registry->getSupportedChannelIds($internal)
        );
    }

    /**
     * A channel an administrator switched off disappears rather than being flagged, so the
     * preferences screen omits the column instead of showing a row of dead switches.
     */
    public function testDisabledChannelDisappearsEntirely(): void
    {
        $registry = new ChannelRegistry(
            [new TestChannel('email'), new TestChannel('teams')],
            ['email' => ['enabled' => false]]
        );

        $this->assertSame(
            [ChannelRegistryInterface::POPUP_CHANNEL, 'teams'],
            $registry->getAvailableChannelIds()
        );
        $this->assertNull($registry->getEnabledChannel('email'));
        $this->assertNotNull($registry->getEnabledChannel('teams'));
    }

    public function testChannelsAreEnabledUnlessConfiguredOtherwise(): void
    {
        $registry = new ChannelRegistry([new TestChannel('email')], []);

        $this->assertNotNull($registry->getEnabledChannel('email'));
    }

    public function testDuplicateChannelNameIsRejected(): void
    {
        $this->expectException(InvalidNotificationChannelException::class);
        $this->expectExceptionMessageMatches('/registered more than once/');

        new ChannelRegistry([new TestChannel('email'), new TestChannel('email')], []);
    }

    /**
     * The pop-up is a preference, not a transport; a channel claiming that name would collide
     * with it in the stored channel set.
     */
    public function testChannelMayNotClaimTheReservedPopupName(): void
    {
        $this->expectException(InvalidNotificationChannelException::class);
        $this->expectExceptionMessageMatches('/reserved/');

        new ChannelRegistry([new TestChannel(ChannelRegistryInterface::POPUP_CHANNEL)], []);
    }
}
