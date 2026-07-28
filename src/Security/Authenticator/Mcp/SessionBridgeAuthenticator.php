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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp;

use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Authenticates MCP requests via Pimcore admin session (cross-context).
 *
 * When the agent-server forwards the browser's PHPSESSID cookie,
 * this authenticator reads `_security_pimcore_admin` from the session
 * to resolve the authenticated Pimcore user.
 *
 * @internal
 */
class SessionBridgeAuthenticator extends AbstractAuthenticator
{
    use McpThrottlingResponseTrait;

    public function __construct(
        private readonly AuthenticationResolverInterface $authenticationResolver,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->hasPreviousSession();
    }

    public function authenticate(Request $request): Passport
    {
        $pimcoreUser = $this->authenticationResolver->authenticateSession($request);

        if (!$pimcoreUser instanceof User) {
            throw new AuthenticationException('No valid Pimcore admin session found.');
        }

        if (!$this->authenticationResolver->isValidUser($pimcoreUser)) {
            throw new AuthenticationException('Pimcore user is not active.');
        }

        $userBadge = new UserBadge(
            $pimcoreUser->getUsername(),
            static fn () => new SecurityUser($pimcoreUser)
        );

        return new SelfValidatingPassport($userBadge);
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return null;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return $this->throttlingResponse($exception);
    }
}
