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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\GeneralNotificationDescriptor;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistry;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistry;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\SubscriptionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\SubscriptionResolver;
use Pimcore\Bundle\StudioBackendBundle\Notification\Hydrator\SubscriptionHydrator;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\UpdateSubscriptionItem;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\UpdateSubscriptionsParameters;
use Pimcore\Bundle\StudioBackendBundle\Notification\Service\SubscriptionService;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestChannel;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestNotificationTypeDescriptor;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * What the service decides on a bulk save. The registries, resolver and hydrator are real — they
 * are the thing under test as much as the service is, and mocking them would only pin the mocks.
 * Only the repository is stubbed, so what would have been persisted can be asserted.
 */
final class SubscriptionServiceTest extends Unit
{
    private const int USER_ID = 7;

    /**
     * An administrator can disable a channel while the preferences screen is open. Rejecting the
     * save would cost the user every other row on the screen for something they cannot influence,
     * so the unavailable channel is dropped and the rest is stored.
     */
    public function testAChannelDisabledByTheAdministratorDoesNotFailTheSave(): void
    {
        $captured = null;
        $service = $this->service(
            [new TestNotificationTypeDescriptor('test.type', allowsExternalDelivery: true)],
            ['email' => ['enabled' => false]],
            $captured
        );

        $service->updateSubscriptions(
            $this->user(),
            new UpdateSubscriptionsParameters([
                new UpdateSubscriptionItem('test.type', true, ['popup', 'email']),
            ])
        );

        $this->assertSame(['popup'], $captured['test.type']['channels']);
        $this->assertTrue($captured['test.type']['subscribed']);
    }

    /**
     * The same treatment for a channel the type structurally cannot use — previously this case was
     * dropped while the administrator case threw, for no reason a caller could have predicted.
     */
    public function testAChannelTheTypeCannotUseIsDropped(): void
    {
        $captured = null;
        $service = $this->service(
            [new TestNotificationTypeDescriptor('test.type', allowsExternalDelivery: false)],
            [],
            $captured
        );

        $service->updateSubscriptions(
            $this->user(),
            new UpdateSubscriptionsParameters([
                new UpdateSubscriptionItem('test.type', true, ['popup', 'email']),
            ])
        );

        $this->assertSame(['popup'], $captured['test.type']['channels']);
    }

    /**
     * Dropping is not silent: the response carries the stored state, and anyone debugging a client
     * gets a log line naming what went and why.
     */
    public function testDroppingAChannelIsLogged(): void
    {
        $captured = null;
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('email'));

        $service = $this->service(
            [new TestNotificationTypeDescriptor('test.type', allowsExternalDelivery: false)],
            [],
            $captured,
            $logger
        );

        $service->updateSubscriptions(
            $this->user(),
            new UpdateSubscriptionsParameters([
                new UpdateSubscriptionItem('test.type', true, ['popup', 'email']),
            ])
        );
    }

    /**
     * Unlike an unavailable channel, an unknown type is not a race an administrator could have
     * caused, and returning the stored state cannot repair the client — so it is still rejected.
     * As a bad field in a request body it must be a 400, not the registry's 404: the caller did
     * not ask for a missing resource.
     */
    public function testAnUnknownTypeIdIsRejectedAsABadRequestRatherThanANotFound(): void
    {
        $captured = null;
        $service = $this->service([new TestNotificationTypeDescriptor('test.type')], [], $captured);

        try {
            $service->updateSubscriptions(
                $this->user(),
                new UpdateSubscriptionsParameters([
                    new UpdateSubscriptionItem('nope.unknown', true, ['popup']),
                ])
            );
            $this->fail('Expected an InvalidArgumentException for an unregistered type id.');
        } catch (NotFoundException) {
            $this->fail('An unknown type id in a request body must not surface as a 404.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('nope.unknown', $e->getMessage());
        }

        $this->assertNull($captured, 'Nothing should be persisted when the payload is rejected.');
    }

    /**
     * The catch-all is locked on so no preference can make a notification vanish without trace.
     */
    public function testALockedTypeCannotBeUnsubscribedFrom(): void
    {
        $captured = null;
        $service = $this->service([], [], $captured);

        $this->expectException(InvalidArgumentException::class);

        $service->updateSubscriptions(
            $this->user(),
            new UpdateSubscriptionsParameters([
                new UpdateSubscriptionItem('info', false, []),
            ])
        );
    }

    /**
     * @param TestNotificationTypeDescriptor[] $descriptors
     * @param array<string, array{enabled: bool}> $channelConfig
     */
    private function service(
        array $descriptors,
        array $channelConfig,
        ?array &$captured,
        ?LoggerInterface $logger = null,
    ): SubscriptionService {
        $general = new GeneralNotificationDescriptor();
        $typeRegistry = new NotificationTypeRegistry($descriptors, $general);
        $channelRegistry = new ChannelRegistry([new TestChannel('email')], $channelConfig);

        $repository = $this->makeEmpty(
            SubscriptionRepositoryInterface::class,
            [
                'getByUser' => [],
                'save' => static function (int $userId, array $preferences) use (&$captured): void {
                    $captured = $preferences;
                },
            ]
        );

        return new SubscriptionService(
            $typeRegistry,
            $channelRegistry,
            new SubscriptionResolver($repository, $typeRegistry, $channelRegistry),
            $repository,
            new SubscriptionHydrator(),
            $this->createMock(EventDispatcherInterface::class),
            $logger ?? $this->createMock(LoggerInterface::class),
        );
    }

    private function user(): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn(self::USER_ID);

        return $user;
    }
}
