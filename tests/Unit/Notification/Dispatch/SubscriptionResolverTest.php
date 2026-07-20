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
use Pimcore\Bundle\StudioBackendBundle\Entity\Notification\NotificationSubscription;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\GeneralNotificationDescriptor;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistry;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistry;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\SubscriptionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\SubscriptionResolver;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestChannel;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestNotificationTypeDescriptor;

final class SubscriptionResolverTest extends Unit
{
    private const int USER_ID = 7;

    public function testDescriptorDefaultsApplyWhenNothingIsStored(): void
    {
        $descriptor = new TestNotificationTypeDescriptor(
            'test.type',
            allowsExternalDelivery: true,
            defaultChannels: ['popup', 'email']
        );

        $subscription = $this->resolve($descriptor, null);

        $this->assertTrue($subscription->isSubscribed());
        $this->assertSame(['popup', 'email'], $subscription->getChannels());
        $this->assertTrue($subscription->wantsPopup());
    }

    public function testStoredRowWinsOverDefaults(): void
    {
        $descriptor = new TestNotificationTypeDescriptor(
            'test.type',
            allowsExternalDelivery: true,
            defaultChannels: ['popup', 'email']
        );

        $subscription = $this->resolve(
            $descriptor,
            new NotificationSubscription(self::USER_ID, 'test.type', true, ['email'])
        );

        $this->assertSame(['email'], $subscription->getChannels());
        $this->assertFalse($subscription->wantsPopup());
    }

    /**
     * Null means "never chosen" and falls back to defaults; an empty array is a deliberate
     * "none". Collapsing the two would silently resurrect defaults for anyone who switched
     * everything off — the bug this distinction exists to prevent.
     */
    public function testEmptyStoredChannelsAreNotTreatedAsUnset(): void
    {
        $descriptor = new TestNotificationTypeDescriptor(
            'test.type',
            allowsExternalDelivery: true,
            defaultChannels: ['popup', 'email']
        );

        $explicitlyNone = $this->resolve(
            $descriptor,
            new NotificationSubscription(self::USER_ID, 'test.type', true, [])
        );
        $this->assertSame([], $explicitlyNone->getChannels());

        $neverChosen = $this->resolve(
            $descriptor,
            new NotificationSubscription(self::USER_ID, 'test.type', true, null)
        );
        $this->assertSame(['popup', 'email'], $neverChosen->getChannels());
    }

    public function testUnsubscribedTypeYieldsNoChannelsAndNoPopup(): void
    {
        $descriptor = new TestNotificationTypeDescriptor(
            'test.type',
            allowsExternalDelivery: true,
            defaultChannels: ['popup', 'email']
        );

        $subscription = $this->resolve(
            $descriptor,
            new NotificationSubscription(self::USER_ID, 'test.type', false, ['popup', 'email'])
        );

        $this->assertFalse($subscription->isSubscribed());
        $this->assertSame([], $subscription->getChannels());
        $this->assertFalse($subscription->wantsPopup());
    }

    /**
     * A stored preference for a channel the type may not use is ignored rather than honoured,
     * so revoking a type's external delivery takes effect immediately.
     */
    public function testChannelsAreNarrowedToWhatTheTypeSupports(): void
    {
        $descriptor = new TestNotificationTypeDescriptor('test.type', allowsExternalDelivery: false);

        $subscription = $this->resolve(
            $descriptor,
            new NotificationSubscription(self::USER_ID, 'test.type', true, ['popup', 'email'])
        );

        $this->assertSame(['popup'], $subscription->getChannels());
    }

    public function testLockedSubscriptionStaysOnEvenIfStoredOff(): void
    {
        $general = new GeneralNotificationDescriptor();

        $subscription = $this->resolve(
            $general,
            new NotificationSubscription(self::USER_ID, 'info', false, []),
            $general
        );

        $this->assertTrue($subscription->isSubscribed());
    }

    /**
     * The pop-up is a presentation preference and must never be handed to a transport.
     */
    public function testTransportChannelsExcludeThePopup(): void
    {
        $descriptor = new TestNotificationTypeDescriptor(
            'test.type',
            allowsExternalDelivery: true,
            defaultChannels: ['popup', 'email']
        );

        $subscription = $this->resolve($descriptor, null);

        $this->assertSame(['email'], $subscription->getTransportChannels());
    }

    private function resolve(
        TestNotificationTypeDescriptor|GeneralNotificationDescriptor $descriptor,
        ?NotificationSubscription $stored,
        ?GeneralNotificationDescriptor $general = null,
    ): \Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\EffectiveSubscription {
        $general ??= new GeneralNotificationDescriptor();

        $repository = $this->makeEmpty(
            SubscriptionRepositoryInterface::class,
            ['getByUserAndType' => $stored]
        );

        $channelRegistry = new ChannelRegistry([new TestChannel('email')], []);

        // The catch-all is always registered; adding it twice would (correctly) trip the
        // duplicate-id guard.
        $descriptors = $descriptor === $general ? [$general] : [$descriptor, $general];
        $typeRegistry = new NotificationTypeRegistry($descriptors, $general);

        $resolver = new SubscriptionResolver($repository, $typeRegistry, $channelRegistry);

        return $resolver->resolve(self::USER_ID, $descriptor);
    }

    public function testPopupIsTheReservedChannelName(): void
    {
        $this->assertSame('popup', ChannelRegistryInterface::POPUP_CHANNEL);
    }
}
