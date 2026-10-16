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

namespace Pimcore\Bundle\StudioBackendBundle\Security\User;

use Pimcore\Security\User\User as PimcoreSecurityUser;
use Pimcore\Security\User\UserChecker as PimcoreUserChecker;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Decorates the core user checker to additionally deny Studio API authentication, on both the
 * json_login and the token-based authenticator, for any account that has two-factor
 * authentication enabled or required. The Studio firewall does not integrate a two-factor
 * challenge, so such an account must be rejected rather than granted password-only access.
 *
 * @internal
 */
final readonly class TwoFactorEnforcingUserChecker implements UserCheckerInterface
{
    public function __construct(private PimcoreUserChecker $decorated)
    {
    }

    /**
     * @throws CustomUserMessageAccountStatusException
     */
    public function checkPreAuth(UserInterface $user): void
    {
        $this->decorated->checkPreAuth($user);
    }

    /**
     * @throws CustomUserMessageAccountStatusException
     */
    public function checkPostAuth(UserInterface $user): void
    {
        $this->decorated->checkPostAuth($user);

        if ($user instanceof PimcoreSecurityUser && $this->requiresTwoFactorAuthentication($user)) {
            throw new CustomUserMessageAccountStatusException(
                'This account requires two-factor authentication, which the Studio API does not support yet.'
            );
        }
    }

    private function requiresTwoFactorAuthentication(PimcoreSecurityUser $user): bool
    {
        $pimcoreUser = $user->getUser();

        return (bool) $pimcoreUser->getTwoFactorAuthentication('enabled')
            || (bool) $pimcoreUser->getTwoFactorAuthentication('required');
    }
}
