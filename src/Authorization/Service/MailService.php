<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Authorization\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Event\LostPasswordEvent;
use Pimcore\Bundle\StudioBackendBundle\Exception\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SettingsProviderInterface;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
final readonly class MailService implements MailServiceInterface
{
    private string $domain;

    public function __construct(
        private SettingsProviderInterface $systemSettingsProvider,
        private RouterInterface $router,
        private EventDispatcherInterface $eventDispatcher,
        private ToolResolverInterface $toolResolver,
    )
    {
        $settings = $this->systemSettingsProvider->getSettings();
        $this->domain = $settings['main_domain'];
    }

    /**
     * @throws DomainConfigurationException|SendMailException
     */
    public function sendResetPasswordMail(UserInterface $user, string $token): void
    {
        if (!$this->domain) {
            throw new DomainConfigurationException();
        }

        $context = $this->router->getContext();
        $context->setHost($this->domain);

        $loginUrl = $this->router->generate(
            'pimcore_admin_login',
            [
                'token' => $token,
                'reset' => 'true',
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        /** @var User $user */
        $event = new LostPasswordEvent($user, $loginUrl);
        $this->eventDispatcher->dispatch($event, LostPasswordEvent::EVENT_NAME);

        // only send mail if it wasn't prevented in event
        if ($event->getSendMail()) {
            try {
                $mail = $this->toolResolver->getMail([$user->getEmail()], 'Pimcore lost password service');
                $mail->setIgnoreDebugMode(true);
                $mail->text(
                    sprintf(
                       "Login to pimcore and change your password using the following link.
                       This temporary login link will expire in 24 hours: \r\n\r\n %s",
                    $loginUrl
                    )
                );
                $mail->send();
            } catch (Exception $e) {
               throw new SendMailException();
            }
        }
    }
}
