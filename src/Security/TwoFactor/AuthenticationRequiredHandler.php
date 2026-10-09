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
use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Replaces scheb's redirect to an HTML form: the Studio API only speaks JSON.
 *
 * @internal
 */
final readonly class AuthenticationRequiredHandler implements AuthenticationRequiredHandlerInterface
{
    public function onAuthenticationRequired(Request $request, TokenInterface $token): Response
    {
        $data = ['message' => 'Two-factor authentication is required.'];

        // Same fields as the login answer, so the UI can show the right step after a login it did not send
        // itself, e.g. an SSO redirect.
        if ($token instanceof TwoFactorTokenInterface) {
            $data['twoFactorRequired'] = true;
            $data['twoFactorStep'] = TwoFactorStep::fromToken($token)->value;
        }

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
