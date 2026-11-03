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
use Pimcore\Bundle\StudioBackendBundle\Document\Repository\SiteRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SettingsProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\Invitation;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ResetPassword;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\TokenLink;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function in_array;

/**
 * @internal
 */
final readonly class UserLoginService implements UserLoginServiceInterface
{
    private const array TRUSTED_URL_ALLOWED_SCHEMES = ['http', 'https'];

    private const string TOKEN_QUERY_PARAMETER = '?token=';

    public function __construct(
        private AuthenticationResolverInterface $authenticationResolver,
        private MailServiceInterface $mailService,
        private LoggerInterface $pimcoreLogger,
        private UrlGeneratorInterface $urlGenerator,
        private UserRepositoryInterface $userRepository,
        private UserResolverInterface $userResolver,
        private SecurityServiceInterface $securityService,
        private SettingsProviderInterface $settingsProvider,
        private SiteRepositoryInterface $siteRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function resetPassword(ResetPassword $resetPassword): void
    {
        $this->validateTrustedUrl($resetPassword->getResetPasswordUrl(), 'reset password URL');

        $user = $this->userResolver->getByName($resetPassword->getUsername());

        $userChecks = $this->userChecks($user);
        if (!$user || !$userChecks['success']) {
            $this->pimcoreLogger->error('Reset password failed', ['error' => $userChecks['error']]);

            return;
        }

        $token = $this->authenticationResolver->generateTokenByUser($user);
        $loginUrl = $resetPassword->getResetPasswordUrl() . self::TOKEN_QUERY_PARAMETER . $token;

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
            return $parameter->getTokenLoginUrl() . self::TOKEN_QUERY_PARAMETER . $token;
        }

        return $this->urlGenerator->generate(
            TokenLoginController::ROUTE_NAME,
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * {@inheritdoc}
     */
    public function sendInvitation(int $id, Invitation $parameter): void
    {
        $this->validateTrustedUrl($parameter->getInvitationUrl(), 'invitation URL');

        $user = $this->userRepository->getUserById($id);

        if ($user->isAdmin() && !$this->securityService->getCurrentUser()->isAdmin()) {
            throw new ForbiddenException(
                'Cannot invite this user',
                HttpResponseErrorKeys::INVITATION_NON_ADMIN->value
            );
        }

        if (!$user->isActive()) {
            throw new InvalidArgumentException(
                'Cannot invite an inactive user',
                errorKey: HttpResponseErrorKeys::INVITATION_USER_INACTIVE->value
            );
        }

        $email = $user->getEmail();
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'Cannot invite a user without a valid email address',
                errorKey: HttpResponseErrorKeys::INVITATION_NO_EMAIL->value
            );
        }

        /** @var User $user */
        // The token is only accepted for a user that has a password: Tool\Authentication::isValidUser()
        // requires one. An invited user usually has none, so give them one they will never learn. It is
        // stored verbatim - hashing happens in the update service - so it can never satisfy password_verify
        // and the account stays password-less in practice. The Classic UI does the same.
        if (!$user->getPassword()) {
            $user->setPassword(bin2hex(random_bytes(16)));
            $this->userRepository->updateUser($user);
        }

        $token = $this->authenticationResolver->generateTokenByUser($user);

        $this->mailService->sendInvitationMail(
            $user,
            $parameter->getInvitationUrl() . self::TOKEN_QUERY_PARAMETER . $token
        );
    }

    private function validateTrustedUrl(string $url, string $subject): void
    {
        $parsed = parse_url($url);

        // Reject anything parse_url() and a browser could read differently: credentials in the
        // authority, backslashes, whitespace and control characters.
        if (
            $parsed === false
            || !isset($parsed['scheme'], $parsed['host'])
            || !in_array(strtolower($parsed['scheme']), self::TRUSTED_URL_ALLOWED_SCHEMES, true)
            || isset($parsed['user'])
            || isset($parsed['pass'])
            || preg_match('/[\x00-\x20\x7f\\\\]/', $url) === 1
        ) {
            throw new InvalidArgumentException(sprintf('Invalid %s provided', $subject));
        }

        if ($this->isRegisteredDomain($parsed['host'])) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf('%s domain does not match any trusted domain', ucfirst($subject))
        );
    }

    private function isRegisteredDomain(string $host): bool
    {
        $settings = $this->settingsProvider->getSettings();
        $mainDomain = $settings['main_domain'] ?? '';

        if ($mainDomain !== '' && $host === $mainDomain) {
            return true;
        }

        foreach ($this->siteRepository->listSites() as $site) {
            if ($host === $site->getMainDomain()) {
                return true;
            }

            if (in_array($host, $site->getDomains(), true)) {
                return true;
            }
        }

        return false;
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
