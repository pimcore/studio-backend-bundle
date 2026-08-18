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
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\EffectiveSubscription;

/**
 * The value object is final readonly, so it is constructed directly rather than mocked.
 */
final class EffectiveSubscriptionTest extends Unit
{
    public function testItSeparatesThePopupPreferenceFromTransports(): void
    {
        $subscription = new EffectiveSubscription('test.type', true, ['popup', 'email', 'teams']);

        $this->assertTrue($subscription->wantsPopup());
        $this->assertSame(['email', 'teams'], $subscription->getTransportChannels());
        $this->assertTrue($subscription->hasChannel('email'));
        $this->assertFalse($subscription->hasChannel('sms'));
    }

    public function testAnUnsubscribedSubscriptionNeverWantsAPopup(): void
    {
        $subscription = new EffectiveSubscription('test.type', false, []);

        $this->assertFalse($subscription->wantsPopup());
        $this->assertSame([], $subscription->getTransportChannels());
    }

    public function testPopupIsTheReservedChannelName(): void
    {
        $this->assertSame('popup', ChannelRegistryInterface::POPUP_CHANNEL);
    }
}
