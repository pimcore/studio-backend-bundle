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

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SettingsProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Event\InvitationEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Service\MailService;
use Pimcore\Mail;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
final class MailServiceTest extends Unit
{
    private const string INVITATION_URL = 'https://mysite.com/pimcore-studio/reset-password/?token=test-token-123';

    public function testSendInvitationMailThrowsWithoutMainDomain(): void
    {
        $service = $this->createService(mainDomain: '');

        $this->expectException(DomainConfigurationException::class);

        $service->sendInvitationMail($this->createUser(), self::INVITATION_URL);
    }

    public function testSendInvitationMailDispatchesInvitationEvent(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatched = null;
        $dispatcher->addListener(
            InvitationEvent::EVENT_NAME,
            static function (InvitationEvent $event) use (&$dispatched): void {
                $dispatched = $event;
            }
        );

        $service = $this->createService(dispatcher: $dispatcher);
        $user = $this->createUser();

        $service->sendInvitationMail($user, self::INVITATION_URL);

        self::assertInstanceOf(InvitationEvent::class, $dispatched);
        self::assertSame(self::INVITATION_URL, $dispatched->getInvitationUrl());
        self::assertSame($user, $dispatched->getUser());
    }

    public function testSendInvitationMailIsNotSentWhenTheEventStopsIt(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            InvitationEvent::EVENT_NAME,
            static function (InvitationEvent $event): void {
                $event->setSendMail(false);
            }
        );

        $mailWasRequested = false;
        $service = $this->createService(
            dispatcher: $dispatcher,
            onGetMail: function () use (&$mailWasRequested): Mail {
                $mailWasRequested = true;

                return $this->makeEmpty(Mail::class);
            }
        );

        $service->sendInvitationMail($this->createUser(), self::INVITATION_URL);

        self::assertFalse($mailWasRequested, 'No mail may be built when the event stops the invitation.');
    }

    public function testSendInvitationMailBodyCarriesTheLink(): void
    {
        $text = null;
        $service = $this->createService(
            onGetMail: function () use (&$text): Mail {
                return $this->makeEmpty(Mail::class, [
                    'text' => function (string $body) use (&$text): Mail {
                        $text = $body;

                        return $this->makeEmpty(Mail::class);
                    },
                ]);
            }
        );

        $service->sendInvitationMail($this->createUser(), self::INVITATION_URL);

        self::assertIsString($text);
        self::assertStringContainsString(self::INVITATION_URL, $text);
        self::assertStringContainsString('24 hours', $text);
    }

    public function testSendInvitationMailWrapsTransportFailures(): void
    {
        $service = $this->createService(
            onGetMail: function (): Mail {
                return $this->makeEmpty(Mail::class, [
                    'send' => static function (): void {
                        throw new Exception('transport down');
                    },
                ]);
            }
        );

        $this->expectException(SendMailException::class);

        $service->sendInvitationMail($this->createUser(), self::INVITATION_URL);
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setName('testuser');
        $user->setEmail('test@example.com');
        $user->setActive(true);

        return $user;
    }

    private function createService(
        string $mainDomain = 'mysite.com',
        ?EventDispatcher $dispatcher = null,
        ?callable $onGetMail = null,
    ): MailService {
        $onGetMail ??= fn (): Mail => $this->makeEmpty(Mail::class);

        return new MailService(
            $this->makeEmpty(SettingsProviderInterface::class, [
                'getSettings' => ['main_domain' => $mainDomain],
            ]),
            $this->makeEmpty(RouterInterface::class, [
                'getContext' => new RequestContext(),
                'generate' => 'https://mysite.com/generated',
            ]),
            $dispatcher ?? new EventDispatcher(),
            $this->makeEmpty(ToolResolverInterface::class, [
                'getHostname' => 'mysite.com',
                'getMail' => $onGetMail,
            ]),
            'noreply@mysite.com',
        );
    }
}
