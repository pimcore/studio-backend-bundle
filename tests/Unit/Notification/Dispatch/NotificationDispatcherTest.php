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
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\NotificationDispatcher;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistry;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\NotificationTypeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\EffectiveSubscription;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription\SubscriptionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestChannel;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestNotificationWriter;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestTypes;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use function in_array;

/**
 * What the dispatcher decides: who is skipped, who gets a bell row, and which channels are handed
 * the result. The write itself is stubbed, so no database is needed.
 */
final class NotificationDispatcherTest extends Unit
{
    private const string TYPE_ID = 'test.type';

    /**
     * A wiring mistake, so unlike a per-recipient failure it is raised — before anything is written.
     */
    public function testUnknownTypeIsRejected(): void
    {
        $writer = new TestNotificationWriter();

        $dispatcher = $this->dispatcher(
            writer: $writer,
            typeRegistry: $this->makeEmpty(
                NotificationTypeRegistryInterface::class,
                [
                    'getType' => static function (string $typeId): never {
                        throw new NotFoundException('Notification type', $typeId, 'type id');
                    },
                ]
            )
        );

        $this->expectException(NotFoundException::class);

        try {
            $dispatcher->dispatch(new DispatchableNotification('unregistered.type', [1], 'Title', 'Message'));
        } finally {
            $this->assertSame([], $writer->written);
        }
    }

    public function testSubscribedRecipientIsWrittenAndHandedToEveryTransportChannel(): void
    {
        $writer = new TestNotificationWriter();
        $email = new TestChannel('email');
        $teams = new TestChannel('teams', sortOrder: 200);

        $dispatcher = $this->dispatcher(
            writer: $writer,
            channels: [$email, $teams],
            subscription: new EffectiveSubscription(self::TYPE_ID, true, ['popup', 'email', 'teams'])
        );

        $dispatcher->dispatch($this->notification([7]));

        $this->assertSame([7], $writer->writtenRecipientIds());
        $this->assertCount(1, $email->sent);
        $this->assertCount(1, $teams->sent);
        $this->assertSame(7, $email->sent[0]['recipient']->getId());
    }

    public function testAPopupOnlySubscriptionInvokesNoTransportChannel(): void
    {
        $writer = new TestNotificationWriter();
        $email = new TestChannel('email');

        $dispatcher = $this->dispatcher(
            writer: $writer,
            channels: [$email],
            subscription: new EffectiveSubscription(self::TYPE_ID, true, ['popup'])
        );

        $dispatcher->dispatch($this->notification([7]));

        $this->assertSame([7], $writer->writtenRecipientIds(), 'The bell row is still written.');
        $this->assertSame([], $email->sent);
    }

    public function testRecipientWithoutTheNotificationsPermissionIsSkipped(): void
    {
        $writer = new TestNotificationWriter();
        $email = new TestChannel('email');

        $dispatcher = $this->dispatcher(
            writer: $writer,
            channels: [$email],
            allowedUserIds: []
        );

        $dispatcher->dispatch($this->notification([7]));

        $this->assertSame([], $writer->written);
        $this->assertSame([], $email->sent);
    }

    public function testUnsubscribedRecipientIsSkipped(): void
    {
        $writer = new TestNotificationWriter();

        $dispatcher = $this->dispatcher(
            writer: $writer,
            subscription: new EffectiveSubscription(self::TYPE_ID, false, [])
        );

        $dispatcher->dispatch($this->notification([7]));

        $this->assertSame([], $writer->written);
    }

    public function testAnUnknownRecipientIsSkippedAndTheRestStillReceive(): void
    {
        $writer = new TestNotificationWriter();

        $dispatcher = $this->dispatcher(writer: $writer, knownUserIds: [7, 9]);

        $dispatcher->dispatch($this->notification([7, 404, 9]));

        $this->assertSame([7, 9], $writer->writtenRecipientIds());
    }

    /**
     * The guarantee ChannelInterface::send() documents.
     */
    public function testABrokenChannelIsLoggedAndTheOtherChannelStillDelivers(): void
    {
        $writer = new TestNotificationWriter();
        $broken = new TestChannel('broken', sortOrder: 10, throwOnSend: true);
        $working = new TestChannel('working', sortOrder: 20);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with($this->stringContains('broken'));

        $dispatcher = $this->dispatcher(
            writer: $writer,
            channels: [$broken, $working],
            subscription: new EffectiveSubscription(self::TYPE_ID, true, ['broken', 'working']),
            logger: $logger
        );

        $dispatcher->dispatch($this->notification([7]));

        $this->assertCount(1, $working->sent, 'The working channel must still have delivered.');
        $this->assertSame([7], $writer->writtenRecipientIds());
    }

    public function testAFailedWriteForOneRecipientDoesNotCostTheOthersTheirNotification(): void
    {
        $writer = new TestNotificationWriter(failForRecipientIds: [8]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with($this->stringContains('user 8'));

        $dispatcher = $this->dispatcher(
            writer: $writer,
            knownUserIds: [7, 8, 9],
            logger: $logger
        );

        $dispatcher->dispatch($this->notification([7, 8, 9]));

        $this->assertSame([7, 9], $writer->writtenRecipientIds());
    }

    private function notification(array $recipientIds): DispatchableNotification
    {
        return new DispatchableNotification(self::TYPE_ID, $recipientIds, 'Title', 'Message');
    }

    /**
     * @param TestChannel[] $channels
     * @param int[] $knownUserIds
     * @param int[]|null $allowedUserIds null means every known user is allowed
     */
    private function dispatcher(
        TestNotificationWriter $writer,
        array $channels = [],
        ?EffectiveSubscription $subscription = null,
        array $knownUserIds = [7],
        ?array $allowedUserIds = null,
        ?LoggerInterface $logger = null,
        ?NotificationTypeRegistryInterface $typeRegistry = null,
    ): NotificationDispatcher {
        $type = TestTypes::type(self::TYPE_ID, allowsExternalDelivery: true);

        return new NotificationDispatcher(
            $typeRegistry ?? $this->makeEmpty(
                NotificationTypeRegistryInterface::class,
                ['getType' => $type]
            ),
            new ChannelRegistry($channels, []),
            $this->makeEmpty(
                SubscriptionResolverInterface::class,
                [
                    'resolve' => $subscription
                        ?? new EffectiveSubscription(self::TYPE_ID, true, ['popup', 'email']),
                ]
            ),
            $writer,
            $this->userRepository($knownUserIds, $allowedUserIds),
            $logger ?? $this->createMock(LoggerInterface::class),
        );
    }

    /**
     * @param int[] $knownUserIds
     * @param int[]|null $allowedUserIds
     */
    private function userRepository(array $knownUserIds, ?array $allowedUserIds): UserRepositoryInterface
    {
        return $this->makeEmpty(
            UserRepositoryInterface::class,
            [
                'getUserById' => function (int $userId) use ($knownUserIds, $allowedUserIds): UserInterface {
                    if (!in_array($userId, $knownUserIds, true)) {
                        throw new NotFoundException('User', $userId);
                    }

                    $allowed = $allowedUserIds === null || in_array($userId, $allowedUserIds, true);

                    $user = $this->createMock(UserInterface::class);
                    $user->method('getId')->willReturn($userId);
                    $user->method('isAllowed')
                        ->willReturnCallback(
                            static fn (string $key): bool => $allowed
                                && $key === UserPermissions::NOTIFICATIONS->value
                        );

                    return $user;
                },
            ]
        );
    }
}
