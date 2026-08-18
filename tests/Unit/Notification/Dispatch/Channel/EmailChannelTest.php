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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Channel;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\EmailChannel;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\Messenger\SendNotificationEmailMessage;
use Pimcore\Model\Asset;
use Pimcore\Model\Notification;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * The channel's job is to turn a notification into a queued email without ever touching the
 * network: it resolves the recipient address, language and an absolute deep link, and enqueues a
 * fully-resolved message. The mail itself is composed in the handler and exercised end-to-end,
 * because Pimcore\Mail cannot be sent without a booted kernel.
 *
 * @internal
 */
final class EmailChannelTest extends Unit
{
    private const string HOST = 'https://demo.example';

    private const string EMAIL = 'jane@example.com';

    public function testNameAndSortOrderAreStable(): void
    {
        $channel = $this->channel(new RequestStack(), $captured);

        self::assertSame('email', $channel->getName());
        self::assertIsInt($channel->getSortOrder());
    }

    public function testSendEnqueuesAMessageMirroringTheBellEntry(): void
    {
        $channel = $this->channel($this->stackWithHost(), $captured);

        $channel->send(
            $this->notification('You were mentioned', 'Jane wrote: ping'),
            $this->recipient(self::EMAIL, 'Jane Doe', 'de')
        );

        self::assertInstanceOf(SendNotificationEmailMessage::class, $captured);
        self::assertSame(self::EMAIL, $captured->getTo());
        self::assertSame('Jane Doe', $captured->getToName());
        self::assertSame('de', $captured->getLocale());
        // Subject and body are the notification's own title and message — nothing more.
        self::assertSame('You were mentioned', $captured->getSubject());
        self::assertSame('You were mentioned', $captured->getTitle());
        self::assertSame('Jane wrote: ping', $captured->getMessage());
    }

    /**
     * A user who switched the email channel on but has no address on their account gets nothing —
     * and would have no way to tell that from the channel being broken, so it is logged.
     */
    public function testSendWithoutARecipientEmailEnqueuesNothingAndSaysWhy(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(self::stringContains('no email address'));

        $channel = new EmailChannel(
            $bus,
            $this->stackWithHost(),
            $this->createMock(ToolResolverInterface::class),
            $logger
        );

        $channel->send($this->notification('t', 'm'), $this->recipient(null, 'Jane', 'en'));
    }

    public function testDeepLinkPointsAtTheLinkedElement(): void
    {
        $channel = $this->channel($this->stackWithHost(), $captured);

        $asset = $this->createMock(Asset::class);
        $asset->method('getId')->willReturn(42);

        $channel->send($this->notification('t', 'm', $asset), $this->recipient(self::EMAIL, 'Jane', 'en'));

        self::assertNotNull($captured);
        self::assertSame(self::HOST . '/pimcore-studio/asset/42', $captured->getLink());
    }

    public function testDeepLinkFallsBackToStudioRootWithoutALinkedElement(): void
    {
        $channel = $this->channel($this->stackWithHost(), $captured);

        $channel->send($this->notification('t', 'm'), $this->recipient(self::EMAIL, 'Jane', 'en'));

        self::assertNotNull($captured);
        self::assertSame(self::HOST . '/pimcore-studio/', $captured->getLink());
    }

    /**
     * A producer (e.g. Collab pointing at a task or discussion) supplies an app-relative deep link
     * in the payload; it wins over the element/root default.
     */
    public function testProducerDeepLinkFromThePayloadIsUsed(): void
    {
        $channel = $this->channel($this->stackWithHost(), $captured);

        $notification = $this->notification('t', 'm');
        $notification->setPayload((string) json_encode(['deepLink' => '/pimcore-studio/?collabThread=42']));

        $channel->send($notification, $this->recipient(self::EMAIL, 'Jane', 'en'));

        self::assertNotNull($captured);
        self::assertSame(self::HOST . '/pimcore-studio/?collabThread=42', $captured->getLink());
    }

    /**
     * The payload is trusted producer data, but a deep link that is not host-relative (an off-site
     * URL, or a "javascript:" scheme) must never become the email button — it is ignored.
     */
    public function testNonHostRelativeDeepLinkIsIgnored(): void
    {
        $channel = $this->channel($this->stackWithHost(), $captured);

        $notification = $this->notification('t', 'm');
        $notification->setPayload((string) json_encode(['deepLink' => 'https://evil.example/phish']));

        $channel->send($notification, $this->recipient(self::EMAIL, 'Jane', 'en'));

        self::assertNotNull($captured);
        self::assertSame(self::HOST . '/pimcore-studio/', $captured->getLink());
    }

    /**
     * "//host" passes a leading-slash check but is protocol-relative, and the host prefix is empty
     * in a worker with no configured domain — which is exactly where it would have escaped.
     */
    public function testProtocolRelativeDeepLinkIsIgnoredWithoutAHost(): void
    {
        $toolResolver = $this->createMock(ToolResolverInterface::class);
        $toolResolver->method('getHostname')->willReturn(null);

        $captured = null;
        $channel = new EmailChannel($this->capturingBus($captured), new RequestStack(), $toolResolver, $this->createMock(LoggerInterface::class));

        $notification = $this->notification('t', 'm');
        $notification->setPayload((string) json_encode(['deepLink' => '//evil.example/phish']));

        $channel->send($notification, $this->recipient(self::EMAIL, 'Jane', 'en'));

        self::assertNotNull($captured);
        self::assertSame('/pimcore-studio/', $captured->getLink());
    }

    /**
     * A Pimcore user need not have a first or last name, and the greeting is "Hi %name%," — an
     * empty one reads "Hi ,".
     */
    public function testGreetingNameFallsBackToTheUsername(): void
    {
        $channel = $this->channel($this->stackWithHost(), $captured);

        $channel->send($this->notification('t', 'm'), $this->recipient(self::EMAIL, '', 'en'));

        self::assertNotNull($captured);
        self::assertSame('jane.doe', $captured->getToName());
    }

    /**
     * No request and no configured domain means the button in the email cannot be made absolute,
     * so it will not resolve for the recipient. Nothing better can be emitted, but it must not be
     * silent.
     */
    public function testAnUnresolvableHostIsLogged(): void
    {
        $toolResolver = $this->createMock(ToolResolverInterface::class);
        $toolResolver->method('getHostname')->willReturn(null);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(self::stringContains('pimcore.general.domain'));

        $captured = null;
        $channel = new EmailChannel($this->capturingBus($captured), new RequestStack(), $toolResolver, $logger);

        $channel->send($this->notification('t', 'm'), $this->recipient(self::EMAIL, 'Jane', 'en'));
    }

    /**
     * With no active request (a CLI-triggered notification) the host falls back to the configured
     * domain rather than producing a broken absolute link.
     */
    public function testDeepLinkUsesTheConfiguredDomainWithoutARequest(): void
    {
        $toolResolver = $this->createMock(ToolResolverInterface::class);
        $toolResolver->method('getHostname')->willReturn('cli.example');
        $toolResolver->method('getRequestScheme')->willReturn('https');

        $captured = null;
        $bus = $this->capturingBus($captured);

        $channel = new EmailChannel($bus, new RequestStack(), $toolResolver, $this->createMock(LoggerInterface::class));
        $channel->send($this->notification('t', 'm'), $this->recipient(self::EMAIL, 'Jane', 'en'));

        self::assertNotNull($captured);
        self::assertSame('https://cli.example/pimcore-studio/', $captured->getLink());
    }

    private function channel(RequestStack $requestStack, ?SendNotificationEmailMessage &$captured): EmailChannel
    {
        return new EmailChannel(
            $this->capturingBus($captured),
            $requestStack,
            $this->createMock(ToolResolverInterface::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    private function capturingBus(?SendNotificationEmailMessage &$captured): MessageBusInterface
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(
            static function (object $message) use (&$captured): Envelope {
                if ($message instanceof SendNotificationEmailMessage) {
                    $captured = $message;
                }

                return new Envelope($message);
            }
        );

        return $bus;
    }

    private function stackWithHost(): RequestStack
    {
        $stack = new RequestStack();
        $stack->push(Request::create(self::HOST . '/pimcore-studio/'));

        return $stack;
    }

    private function notification(string $title, string $message, ?Asset $linkedElement = null): Notification
    {
        $notification = new Notification();
        $notification->setTitle($title);
        $notification->setMessage($message);
        if ($linkedElement !== null) {
            $notification->setLinkedElement($linkedElement);
        }

        return $notification;
    }

    private function recipient(?string $email, string $fullName, string $language): UserInterface
    {
        $recipient = $this->createMock(UserInterface::class);
        $recipient->method('getEmail')->willReturn($email);
        $recipient->method('getFullName')->willReturn($fullName);
        $recipient->method('getUsername')->willReturn('jane.doe');
        $recipient->method('getLanguage')->willReturn($language);

        return $recipient;
    }
}
