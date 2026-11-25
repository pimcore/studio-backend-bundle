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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SettingsProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Event\LostPasswordEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Service\MailService;
use Pimcore\Mail;
use Pimcore\Model\User;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
final class MailServiceTest extends Unit
{
    private const string TEMPLATE_URL = 'https://mysite.com/{{path(pimcore_object(2).getEmail())}}?token=abc';

    /**
     * SystemSettingsProvider::getSettings() throws while the configured error document is gone
     * (see SystemSettingsProviderTest), which made every consumer unbuildable
     * (pimcore/platform-version#864).
     *
     * @throws Exception
     */
    public function testServiceIsBuiltWhileTheSystemSettingsCannotBeResolved(): void
    {
        $settingsProvider = $this->makeEmpty(SettingsProviderInterface::class, [
            'getSettings' => static function (): array {
                throw new NotFoundException('document', '/en/missing-error-page', 'path');
            },
        ]);

        $this->assertInstanceOf(MailService::class, $this->getService($settingsProvider));
    }

    /**
     * @throws Exception
     */
    public function testMissingMainDomainIsStillReportedOnSend(): void
    {
        $settingsProvider = $this->createMock(SettingsProviderInterface::class);
        $settingsProvider->expects($this->once())
            ->method('getSettings')
            ->willReturn(['main_domain' => '']);

        $service = $this->getService($settingsProvider);

        // resolved once, reported on every send
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $service->sendResetPasswordMail($this->createUser(), 'token');
                $this->fail('Expected a DomainConfigurationException');
            } catch (DomainConfigurationException $exception) {
                $this->assertSame(500, $exception->getStatusCode());
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testConfiguredMainDomainIsAppliedToTheRouterContext(): void
    {
        $context = new RequestContext();
        $router = $this->makeEmpty(RouterInterface::class, ['getContext' => $context]);
        $settingsProvider = $this->makeEmpty(SettingsProviderInterface::class, [
            'getSettings' => ['main_domain' => 'studio.example.com'],
        ]);

        $this->getService($settingsProvider, $router)->sendResetPasswordMail(
            $this->createUser(),
            'token',
            'https://studio.example.com/login?token=token'
        );

        $this->assertSame('studio.example.com', $context->getHost());
    }

    /**
     * @throws Exception
     */
    public function testResetPasswordMailIsSentWithoutRenderingTheTextAsTemplate(): void
    {
        $text = null;
        $mail = $this->createMock(Mail::class);
        $mail->method('text')->willReturnCallback(
            static function (string $body) use (&$text, $mail): Mail {
                $text = $body;

                return $mail;
            }
        );
        // send() compiles the text body as a Twig template, sendWithoutRendering() sends it as it is
        $mail->expects($this->never())->method('send');
        $mail->expects($this->once())->method('sendWithoutRendering')->willReturnSelf();

        $this->getSendingService($mail)->sendResetPasswordMail($this->createUser(), 'abc', self::TEMPLATE_URL);

        $this->assertIsString($text);
        $this->assertStringEndsWith(self::TEMPLATE_URL, $text, 'The link must reach the mail unchanged.');
    }

    /**
     * @throws Exception
     */
    public function testFailureToSendDoesNotExposeTheCauseInTheApiMessage(): void
    {
        $cause = new RuntimeException('Route "secret-value-read-from-the-system" not found');
        $mail = $this->createMock(Mail::class);
        $mail->method('sendWithoutRendering')->willThrowException($cause);

        try {
            $this->getSendingService($mail)->sendResetPasswordMail($this->createUser(), 'abc', self::TEMPLATE_URL);
            $this->fail('A SendMailException was expected.');
        } catch (SendMailException $exception) {
            $this->assertSame('Failed to send reset password mail', $exception->getMessage());
            $this->assertStringNotContainsString('secret-value', $exception->getMessage());
            $this->assertSame($cause, $exception->getPrevious(), 'The cause stays available for the log.');
        }
    }

    private function createUser(): User
    {
        return (new User())->setEmail('user@example.com');
    }

    /**
     * @throws Exception
     */
    private function getService(
        SettingsProviderInterface $settingsProvider,
        ?RouterInterface $router = null
    ): MailService {
        return new MailService(
            $settingsProvider,
            $router ?? $this->makeEmpty(RouterInterface::class, ['getContext' => new RequestContext()]),
            $this->makeEmpty(EventDispatcherInterface::class, [
                'dispatch' => static fn (LostPasswordEvent $event): LostPasswordEvent => $event->setSendMail(false),
            ]),
            $this->makeEmpty(ToolResolverInterface::class, ['getMail' => $this->makeEmpty(Mail::class)]),
            'noreply@example.com',
        );
    }

    /**
     * A service whose event listener lets the mail go out, sending it through the given mail.
     *
     * @throws Exception
     */
    private function getSendingService(Mail $mail): MailService
    {
        return new MailService(
            $this->makeEmpty(SettingsProviderInterface::class, [
                'getSettings' => ['main_domain' => 'mysite.com'],
            ]),
            $this->makeEmpty(RouterInterface::class, ['getContext' => new RequestContext()]),
            $this->makeEmpty(EventDispatcherInterface::class, [
                'dispatch' => static fn (object $event): object => $event,
            ]),
            $this->makeEmpty(ToolResolverInterface::class, ['getMail' => $mail]),
            'noreply@example.com',
        );
    }
}
