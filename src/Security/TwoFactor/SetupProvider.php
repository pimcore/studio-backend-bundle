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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UnprocessableContentException;
use Pimcore\Bundle\StudioBackendBundle\User\Service\TwoFactorServiceInterface;
use Pimcore\Security\User\User;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The login step for users who must use two-factor authentication but have not set it up: the
 * Google provider never starts for them, so without this step the password alone would log them in.
 * The first code from the new secret completes both the setup and the login.
 *
 * @internal
 */
final readonly class SetupProvider implements TwoFactorProviderInterface
{
    public const string ALIAS = 'studio_setup';

    public function __construct(
        private TwoFactorServiceInterface $twoFactorService,
    ) {
    }

    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        $user = $context->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $pimcoreUser = $user->getUser();

        return $pimcoreUser->getTwoFactorAuthentication('required')
            && !$pimcoreUser->getTwoFactorAuthentication('enabled');
    }

    public function needsPreparation(): bool
    {
        return false;
    }

    public function prepareAuthentication(object $user): void
    {
        // Nothing to prepare: the secret is created by the setup endpoint, not when the login starts.
    }

    public function validateAuthenticationCode(object $user, string $authenticationCode): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        try {
            $this->twoFactorService->confirmSetup($user->getUser(), $authenticationCode);
        } catch (ConflictException|ForbiddenException|UnprocessableContentException) {
            return false;
        }

        return true;
    }

    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        // Studio answers in JSON (LoginSuccessHandler); scheb's HTML form is never shown.
        return new class() implements TwoFactorFormRendererInterface {
            public function renderForm(Request $request, array $templateVars): Response
            {
                return new Response('', Response::HTTP_NOT_FOUND);
            }
        };
    }
}
