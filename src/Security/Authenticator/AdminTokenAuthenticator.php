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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Authenticator;

use JsonException;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Controller\TokenLoginController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Security\User\User;
use Pimcore\Tool\Authentication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @internal
 */
class AdminTokenAuthenticator extends AbstractAuthenticator
{
    /**
     *  @throws InvalidArgumentException
     */
    public function supports(Request $request): ?bool
    {
        return ($request->attributes->get('_route') === TokenLoginController::ROUTE_NAME) &&
            $this->getTokenFromRequest($request) !== null;
    }

    /**
     *  @throws AccessDeniedException|InvalidArgumentException
     */
    public function authenticate(Request $request): Passport
    {
        $pimcoreUser = Authentication::authenticateToken(
            $this->getTokenFromRequest($request),
        );

        if ($pimcoreUser === null) {
            throw new AccessDeniedException('Failed to authenticate with username and token');
        }

        $pimcoreUser->setTwoFactorAuthentication('required', false);

        $userBadge = new UserBadge($pimcoreUser->getUsername(), function () use ($pimcoreUser) {
            return new User($pimcoreUser);
        });

        return new SelfValidatingPassport($userBadge);

    }

    /**
     * @throws AccessDeniedException
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        throw new AccessDeniedException('Failed to authenticate with username and token');
    }

    /**
     * @throws AccessDeniedException
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $securityUser = $token->getUser();
        if (!$securityUser instanceof UserInterface) {
            throw new AccessDeniedException(
                sprintf('Invalid user object. User has to be instance of %s',
                    UserInterface::class
                )
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        return null;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getTokenFromRequest(Request $request): ?string
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Invalid data string provided');
        }

        return $data['token'] ?? null;
    }
}
