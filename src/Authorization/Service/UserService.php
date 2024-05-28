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

use JetBrains\PhpStorm\ArrayShape;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Authorization\RateLimiter\RateLimiterInterface;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Schema\ResetPassword;
use Pimcore\Bundle\StudioBackendBundle\Exception\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Exception\SendMailException;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final readonly class UserService implements UserServiceInterface
{
    public function __construct(
        private AuthenticationResolverInterface $authenticationResolver,
        private UserResolverInterface $userResolver,
        private MailServiceInterface $mailService,
        private RateLimiterInterface $rateLimiter,
        private LoggerInterface $pimcoreLogger
    )
    {
    }

    /**
     * @throws RateLimitException|DomainConfigurationException|SendMailException
     */
    public function resetPassword(ResetPassword $resetPassword): void
    {
        $this->rateLimiter->check();

        $user = $this->userResolver->getByName($resetPassword->getUsername());

        $userChecks = $this->userChecks($user);

        if(!$user || !$userChecks['success']) {
            $this->pimcoreLogger->error('Reset password failed', ['error' => $userChecks['error']]);
            return;
        }

        $token = $this->authenticationResolver->generateTokenByUser($user);

        try {
            $this->mailService->sendResetPasswordMail($user, $token);
        } catch (DomainConfigurationException|SendMailException $exception) {
            $this->pimcoreLogger->error('Error sending password recovery email', ['error' => $exception->getMessage()]);
            throw $exception;
        }

    }

    #[ArrayShape(['success' => 'boolean', 'error' => 'string'])]
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