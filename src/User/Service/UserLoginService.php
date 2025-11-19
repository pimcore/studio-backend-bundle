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

use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Controller\TokenLoginController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\RateLimiter\RateLimiterInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ResetPassword;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\TokenLink;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
final readonly class UserLoginService implements UserLoginServiceInterface
{
    public function __construct(
        private AuthenticationResolverInterface $authenticationResolver,
        private MailServiceInterface $mailService,
        private RateLimiterInterface $rateLimiter,
        private LoggerInterface $pimcoreLogger,
        private UrlGeneratorInterface $urlGenerator,
        private UserRepositoryInterface $userRepository,
        private UserResolverInterface $userResolver,
        private SecurityServiceInterface $securityService,
    ) {
    }

    /**
     * @throws RateLimitException|DomainConfigurationException|SendMailException
     */
    public function resetPassword(ResetPassword $resetPassword): void
    {
        $this->rateLimiter->check();

        $user = $this->userResolver->getByName($resetPassword->getUsername());

        $userChecks = $this->userChecks($user);
        if (!$user || !$userChecks['success']) {
            $this->pimcoreLogger->error('Reset password failed', ['error' => $userChecks['error']]);

            return;
        }

        $token = $this->authenticationResolver->generateTokenByUser($user);
        $loginUrl = null;
        if ($resetPassword->getResetPasswordUrl() !== null) {
            $loginUrl = $resetPassword->getResetPasswordUrl() . '?token=' . $token;
        }

        try {
            $this->mailService->sendResetPasswordMail($user, $token, $loginUrl);
        } catch (DomainConfigurationException|SendMailException $exception) {
            $this->pimcoreLogger->error('Error sending password recovery email', ['error' => $exception->getMessage()]);

            throw $exception;
        }

    }

    public function getLoginTokenUrl(int $id, TokenLink $parameter): string
    {
        $user = $this->userRepository->getUserById($id);
        if ($user->isAdmin() && !$this->securityService->getCurrentUser()->isAdmin()) {
            throw new ForbiddenException(
                'Cannot generate login token URL for this user',
                HttpResponseErrorKeys::LOGIN_TOKEN_NON_ADMIN->value
            );
        }

        if ($user->getPassword() === null || $user->getPassword() === '') {
            throw new ForbiddenException(
                'Cannot generate login token URL for this user without password set',
                HttpResponseErrorKeys::LOGIN_TOKEN_NO_PASSWORD->value
            );
        }

        /** @var User $user */
        $token = $this->authenticationResolver->generateTokenByUser($user);

        if ($parameter->getTokenLoginUrl() !== '') {
            return $parameter->getTokenLoginUrl() . '?token=' . $token;
        }

        return $this->urlGenerator->generate(
            TokenLoginController::ROUTE_NAME,
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @return array<string, bool|string>
     */
    private function userChecks(?UserInterface $user): array
    {
        if (!$user) {
            return ['success' => false, 'error' => 'user_unknown'];
        }

        if (!$user->getEmail() || !filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'user_no_email_address'];
        }

        if (!$user->isActive()) {
            return ['success' => false, 'error' => 'user_inactive'];
        }

        if (!$user->getPassword()) {
            return ['success' => false, 'error' => 'user_no_password'];
        }

        return ['success' => true, 'error' => ''];
    }
}
