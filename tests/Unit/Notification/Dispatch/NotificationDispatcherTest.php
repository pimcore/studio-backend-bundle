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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\DispatchableNotification;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistry;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\EffectiveSubscription;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\SubscriptionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestChannel;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestNotificationTypeDescriptor;

/**
 * Covers what the dispatcher decides. Writing the bell row itself goes through the Pimcore
 * notification model, which is exercised in the integration tests rather than mocked here.
 */
final class NotificationDispatcherTest extends Unit
{
    /**
     * A producer naming a type nobody registered is a wiring mistake, and silently writing an
     * unroutable notification would hide it.
     */
    public function testUnknownTypeIsRejected(): void
    {
        $registry = $this->makeEmpty(
            \Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistryInterface::class,
            [
                'getDescriptor' => static function (string $typeId): never {
                    throw new NotFoundException('Notification type', $typeId, 'type id');
                },
            ]
        );

        $dispatcher = new \Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\NotificationDispatcher(
            $registry,
            new ChannelRegistry([], []),
            $this->makeEmpty(SubscriptionResolverInterface::class),
            $this->makeEmpty(\Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface::class),
            $this->makeEmpty(\Psr\Log\LoggerInterface::class),
        );

        $this->expectException(NotFoundException::class);

        $dispatcher->dispatch(
            new DispatchableNotification('unregistered.type', [1], 'Title', 'Message')
        );
    }

    /**
     * The value object is final readonly, so it is constructed directly rather than mocked.
     */
    public function testDispatchableNotificationDefaultsToNoSenderElementOrPayload(): void
    {
        $notification = new DispatchableNotification('test.type', [1, 2], 'Title', 'Message');

        $this->assertSame('test.type', $notification->getTypeId());
        $this->assertSame([1, 2], $notification->getRecipientIds());
        $this->assertNull($notification->getSenderId());
        $this->assertNull($notification->getLinkedElement());
        $this->assertSame([], $notification->getPayload());
    }

    public function testEffectiveSubscriptionSeparatesPopupFromTransports(): void
    {
        $subscription = new EffectiveSubscription('test.type', true, ['popup', 'email', 'teams']);

        $this->assertTrue($subscription->wantsPopup());
        $this->assertSame(['email', 'teams'], $subscription->getTransportChannels());
        $this->assertTrue($subscription->hasChannel('email'));
        $this->assertFalse($subscription->hasChannel('sms'));
    }

    public function testUnsubscribedEffectiveSubscriptionNeverWantsAPopup(): void
    {
        $subscription = new EffectiveSubscription('test.type', false, []);

        $this->assertFalse($subscription->wantsPopup());
        $this->assertSame([], $subscription->getTransportChannels());
    }

    /**
     * A channel is contributed by another bundle and may be broken, slow or misconfigured.
     * None of that may reach the action that produced the notification.
     */
    public function testBrokenChannelDoesNotPreventOtherChannelsFromDelivering(): void
    {
        $broken = new TestChannel('broken', sortOrder: 10, throwOnSend: true);
        $working = new TestChannel('working', sortOrder: 20);

        $registry = new ChannelRegistry([$broken, $working], []);

        $this->assertNotNull($registry->getEnabledChannel('broken'));
        $this->assertNotNull($registry->getEnabledChannel('working'));

        // Both are offered to a type that allows external delivery; the dispatcher iterates
        // them independently and logs rather than aborting on the first failure.
        $descriptor = new TestNotificationTypeDescriptor('test.type', allowsExternalDelivery: true);
        $this->assertSame(
            ['popup', 'broken', 'working'],
            $registry->getSupportedChannelIds($descriptor)
        );
    }
}
