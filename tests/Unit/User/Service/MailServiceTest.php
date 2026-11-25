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
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SettingsProviderInterface;
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

        $this->createService($mail)->sendResetPasswordMail($this->createUser(), 'abc', self::TEMPLATE_URL);

        $this->assertIsString($text);
        $this->assertStringEndsWith(self::TEMPLATE_URL, $text, 'The link must reach the mail unchanged.');
    }

    public function testFailureToSendDoesNotExposeTheCauseInTheApiMessage(): void
    {
        $cause = new RuntimeException('Route "secret-value-read-from-the-system" not found');
        $mail = $this->createMock(Mail::class);
        $mail->method('sendWithoutRendering')->willThrowException($cause);

        try {
            $this->createService($mail)->sendResetPasswordMail($this->createUser(), 'abc', self::TEMPLATE_URL);
            $this->fail('A SendMailException was expected.');
        } catch (SendMailException $exception) {
            $this->assertSame('Failed to send reset password mail', $exception->getMessage());
            $this->assertStringNotContainsString('secret-value', $exception->getMessage());
            $this->assertSame($cause, $exception->getPrevious(), 'The cause stays available for the log.');
        }
    }

    private function createService(Mail $mail): MailService
    {
        return new MailService(
            $this->makeEmpty(SettingsProviderInterface::class, [
                'getSettings' => ['main_domain' => 'mysite.com'],
            ]),
            $this->makeEmpty(RouterInterface::class, [
                'getContext' => new RequestContext(),
            ]),
            $this->makeEmpty(EventDispatcherInterface::class, [
                'dispatch' => static fn (object $event): object => $event,
            ]),
            $this->makeEmpty(ToolResolverInterface::class, [
                'getMail' => $mail,
            ]),
            'no-reply@mysite.com',
        );
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setName('testuser');
        $user->setEmail('test@example.com');

        return $user;
    }
}
