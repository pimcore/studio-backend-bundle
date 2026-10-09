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

namespace Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Answers both the password and the code step of the Studio login. Without it, a password that
 * still needs a code would reach the access check and get a 401 instead of the next step.
 *
 * @internal
 */
final readonly class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        if ($token instanceof TwoFactorTokenInterface) {
            return new JsonResponse([
                'twoFactorRequired' => true,
                'twoFactorStep' => TwoFactorStep::fromToken($token)->value,
            ]);
        }

        return new Response();
    }
}
