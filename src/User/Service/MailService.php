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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SettingsProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Event\InvitationEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Event\LostPasswordEvent;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use function sprintf;

/**
 * @internal
 */
final class MailService implements MailServiceInterface
{
    private const string RESET_MAIL_TEXT = 'Login to pimcore and change your password using the following link. ' .
        "This temporary login link will expire in 24 hours: \r\n\r\n%s";

    private const string INVITATION_MAIL_TEXT = 'You have been invited to Pimcore. ' .
        'Set your password using the following link. ' .
        "This temporary login link will expire in 24 hours: \r\n\r\n%s";

    private ?array $systemSettings = null;

    public function __construct(
        private readonly SettingsProviderInterface $systemSettingsProvider,
        private readonly RouterInterface $router,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ToolResolverInterface $toolResolver,
        private readonly string $fromEmail,
    ) {
    }

    /**
     * @throws DomainConfigurationException|SendMailException
     */
    public function sendResetPasswordMail(UserInterface $user, string $token, ?string $loginUrl = null): void
    {
        $domain = $this->getMainDomain();
        if (!$domain) {
            throw new DomainConfigurationException();
        }

        $context = $this->router->getContext();
        $context->setHost($domain);

        if ($loginUrl === null) {
            $loginUrl = $this->router->generate(
                'pimcore_admin_login',
                [
                    'token' => $token,
                    'reset' => 'true',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        /** @var User $user */
        $event = new LostPasswordEvent($user, $loginUrl);
        $this->eventDispatcher->dispatch($event, LostPasswordEvent::EVENT_NAME);

        // only send mail if it wasn't prevented in the event
        if ($event->getSendMail()) {
            try {
                $mail = $this->toolResolver->getMail([$user->getEmail()], 'Pimcore lost password service');
                $mail->setIgnoreDebugMode(true);
                $mail->addFrom($this->fromEmail);
                $mail->text(sprintf(self::RESET_MAIL_TEXT, $loginUrl));
                // The text holds a link the caller has a say in, it must never be compiled as a Twig template.
                $mail->sendWithoutRendering();
            } catch (Exception $exception) {
                throw new SendMailException($exception);
            }
        }
    }

    /**
     * @throws DomainConfigurationException|SendMailException
     */
    public function sendInvitationMail(UserInterface $user, string $invitationUrl): void
    {
        $domain = $this->getMainDomain();
        if (!$domain) {
            throw new DomainConfigurationException();
        }

        $context = $this->router->getContext();
        $context->setHost($domain);

        /** @var User $user */
        $event = new InvitationEvent($user, $invitationUrl);
        $this->eventDispatcher->dispatch($event, InvitationEvent::EVENT_NAME);

        // only send mail if it wasn't prevented in the event
        if ($event->getSendMail()) {
            try {
                $mail = $this->toolResolver->getMail(
                    [$user->getEmail()],
                    'Pimcore login invitation for ' . $this->toolResolver->getHostname()
                );
                $mail->setIgnoreDebugMode(true);
                $mail->addFrom($this->fromEmail);
                $mail->text(sprintf(self::INVITATION_MAIL_TEXT, $invitationUrl));
                // The text holds a link, it must never be compiled as a Twig template.
                $mail->sendWithoutRendering();
            } catch (Exception $exception) {
                throw new SendMailException($exception, 'invitation mail');
            }
        }
    }

    /**
     * Resolved on first use and memoised: the system settings cannot be read in the constructor,
     * because they throw when a configured error document is missing.
     */
    private function getMainDomain(): string
    {
        $this->systemSettings ??= $this->systemSettingsProvider->getSettings();

        return $this->systemSettings['main_domain'];
    }
}
