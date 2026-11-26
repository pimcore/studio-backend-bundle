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
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ResetPassword;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function in_array;
use function is_string;
use function rtrim;
use function sprintf;
use function urlencode;

/**
 * @internal
 */
final readonly class UserLoginService implements UserLoginServiceInterface
{
    private const array RESET_PASSWORD_URL_ALLOWED_SCHEMES = ['http', 'https'];

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
        private string $studioPath = '/pimcore-studio',
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function resetPassword(ResetPassword $resetPassword): void
    {
        $this->validateResetPasswordUrl($resetPassword->getResetPasswordUrl());

        $user = $this->userResolver->getByName($resetPassword->getUsername());

        $userChecks = $this->userChecks($user);
        if (!$user || !$userChecks['success']) {
            $this->pimcoreLogger->error('Reset password failed', ['error' => $userChecks['error']]);

            return;
        }

        $token = $this->authenticationResolver->generateTokenByUser($user);
        $loginUrl = $resetPassword->getResetPasswordUrl() . '?token=' . $token;

        try {
            $this->mailService->sendResetPasswordMail($user, $token, $loginUrl);
        } catch (DomainConfigurationException|SendMailException $exception) {
            $this->pimcoreLogger->error(
                'Error sending password recovery email',
                ['error' => $exception->getPrevious()?->getMessage() ?? $exception->getMessage()]
            );

            throw $exception;
        }
    }

    public function getLoginTokenUrl(int $id): string
    {
        $user = $this->userRepository->getPersonById($id);
        $currentUser = $this->securityService->getCurrentUser();

        if ($user->isAdmin() && !$currentUser->isAdmin()) {
            throw new ForbiddenException(
                'Cannot generate login token URL for this user',
                HttpResponseErrorKeys::LOGIN_TOKEN_NON_ADMIN->value
            );
        }

        // only superusers may log in as somebody else; everyone else only gets a link for their own account
        if (!$currentUser->isAdmin() && $user->getId() !== $currentUser->getId()) {
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

        return $this->buildStudioTokenUrl('/login/', $token);
    }

    /**
     * {@inheritdoc}
     */
    public function sendInvitation(int $id): void
    {
        // getPersonById, not getUserById: a service user must not be invited either
        $user = $this->userRepository->getPersonById($id);

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

        // Resolved before anything is written: without a main domain no safe link can be built, and the
        // placeholder password must not be stored for an invitation that is never sent.
        $origin = $this->getMainDomainOrigin();

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

        // The reset password route logs the invitee in with the token and opens the profile in reset
        // mode, so they set a password of their own. The plain login route would drop them at the root
        // holding a password they do not know.
        $this->mailService->sendInvitationMail(
            $user,
            $this->buildStudioTokenUrl('/reset-password/', $token, $origin)
        );
    }

    /**
     * A Studio URL carrying the token. Without an explicit origin it is taken from the router context,
     * which follows the current request: only use that for a link handed back to the caller themselves.
     */
    private function buildStudioTokenUrl(string $path, string $token, ?string $origin = null): string
    {
        return sprintf(
            '%s%s%s%s?token=%s',
            $origin ?? $this->getOrigin(),
            $this->urlGenerator->getContext()->getBaseUrl(),
            rtrim($this->studioPath, '/'),
            $path,
            urlencode($token)
        );
    }

    /**
     * The origin for a link mailed to someone other than the caller. Its host must never come from the
     * request, because the Host header is chosen by the caller; only the configured main domain is used,
     * including a port it may carry.
     *
     * @throws InvalidArgumentException
     */
    private function getMainDomainOrigin(): string
    {
        $mainDomain = $this->settingsProvider->getSettings()['main_domain'] ?? '';

        if (!is_string($mainDomain) || $mainDomain === '') {
            throw new InvalidArgumentException(
                'Cannot invite a user while no main domain is set in the system settings',
                errorKey: HttpResponseErrorKeys::INVITATION_NO_MAIN_DOMAIN->value
            );
        }

        return sprintf('%s://%s', $this->urlGenerator->getContext()->getScheme(), $mainDomain);
    }

    /**
     * Scheme, host and port the Studio is served from, taken from the router context
     * (the same origin the absolute API URLs are generated for). The application base URL
     * (e.g. /cms) is added separately by the caller.
     */
    private function getOrigin(): string
    {
        $apiUrl = $this->urlGenerator->generate(
            TokenLoginController::ROUTE_NAME,
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $parts = parse_url($apiUrl);

        return sprintf(
            '%s://%s%s',
            $parts['scheme'] ?? 'https',
            $parts['host'] ?? '',
            isset($parts['port']) ? ':' . $parts['port'] : ''
        );
    }

    private function validateResetPasswordUrl(string $url): void
    {
        $parsed = parse_url($url);

        // Reject anything parse_url() and a browser could read differently: credentials in the
        // authority, backslashes, whitespace and control characters. Curly braces are no valid
        // URL characters either and would open template syntax wherever the link ends up.
        if (
            $parsed === false
            || !isset($parsed['scheme'], $parsed['host'])
            || !in_array(strtolower($parsed['scheme']), self::RESET_PASSWORD_URL_ALLOWED_SCHEMES, true)
            || isset($parsed['user'])
            || isset($parsed['pass'])
            || preg_match('/[\x00-\x20\x7f\\\\{}]/', $url) === 1
        ) {
            throw new InvalidArgumentException('Invalid reset password URL provided');
        }

        if ($this->isRegisteredDomain($parsed['host'])) {
            return;
        }

        throw new InvalidArgumentException(
            'Reset password URL domain does not match any trusted domain'
        );
    }

    private function isRegisteredDomain(string $host): bool
    {
        $settings = $this->settingsProvider->getSettings();
        $mainDomain = $settings['main_domain'] ?? '';

        // The main domain may carry a port (the invitation link keeps it), the host compared here never does
        if (is_string($mainDomain) && $mainDomain !== '' && $host === $this->getHostname($mainDomain)) {
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
     * The hostname of a configured domain, without the port it may carry.
     */
    private function getHostname(string $domain): string
    {
        $host = parse_url('//' . $domain, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : $domain;
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
