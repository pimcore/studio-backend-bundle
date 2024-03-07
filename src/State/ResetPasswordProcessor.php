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

namespace Pimcore\Bundle\StudioApiBundle\State;

use ApiPlatform\Exception\InvalidValueException;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Exception;
use Pimcore\Bundle\AdminBundle\Event\AdminEvents;
use Pimcore\Bundle\AdminBundle\Event\Login\LostPasswordEvent;
use Pimcore\Bundle\StudioApiBundle\Dto\ResetPasswordRequest;
use Pimcore\Logger;
use Pimcore\Model\User;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tool;
use Pimcore\Tool\Authentication;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class ResetPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private RequestStack $requestStack,
        private RateLimiterFactory $resetPasswordLimiter,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (
            !$operation instanceof Post ||
            !$data instanceof ResetPasswordRequest ||
            $operation->getUriTemplate() !== '/users/reset-password'
        ) {
            // wrong operation
            throw new OperationNotFoundException();
        }

        $user = User::getByName($data->getUsername());
        if (!$user instanceof User) {
            return $data;
        }

        $currentRequest = $this->requestStack->getCurrentRequest();
        $limiter = $this->resetPasswordLimiter->create($currentRequest->getClientIp());

        if ($limiter->consume()->isAccepted() === false) {
            throw new InvalidValueException('Rate limit exceeded');
        }

        if (!$user->isActive()) {
            throw new InvalidValueException('User is inactive');
        }

        if (!$user->getEmail()) {
            throw new InvalidValueException('User has no email address');
        }

        if (!$user->getPassword()) {
            throw new InvalidValueException('User has no password');
        }

        $error = null;
        $token = Authentication::generateTokenByUser($user);

        try {
            $domain = SystemSettingsConfig::get()['general']['domain'];
            if (!$domain) {
                throw new Exception('No main domain set in system settings, unable to generate reset password link');
            }

            $routerContext = $this->router->getContext();
            $routerContext->setHost($domain);

            $loginUrl = $this->router->generate(
                'pimcore_admin_login_check',
                [
                    'token' => $token,
                    'reset' => 'true',
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            //@TODO: get rid off admin ui dependencies -> move to core (?)
            $event = new LostPasswordEvent($user, $loginUrl);
            $this->eventDispatcher->dispatch($event, AdminEvents::LOGIN_LOSTPASSWORD);

            // only send mail if it wasn't prevented in event
            if ($event->getSendMail()) {
                $mail = Tool::getMail([$user->getEmail()], 'Pimcore lost password service');
                $mail->setIgnoreDebugMode(true);
                $mail->text(
                    'Login to pimcore and change your password using the following link. '.
                    "This temporary login link will expire in 24 hours: \r\n\r\n" . $loginUrl
                );
                $mail->send();
            }

            // directly return event response
            if ($event->hasResponse()) {
                return $event->getResponse();
            }
        } catch (Exception $e) {
            Logger::error('Error sending password recovery email: ' . $e->getMessage());
            $error = 'lost_password_email_error';
        }

        if ($error) {
            Logger::error('Lost password service: ' . $error);
        }

        return $data;
    }
}
