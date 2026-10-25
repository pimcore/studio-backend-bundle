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

namespace Pimcore\Bundle\StudioBackendBundle\Authorization\Service;

use Pimcore\Bundle\StudioBackendBundle\Authorization\Schema\SessionStatus;
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\TwoFactorStep;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @internal
 */
final readonly class SessionStatusService implements SessionStatusServiceInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private AuthorizationCheckerInterface $authorizationChecker,
        #[Autowire(param: 'scheb_two_factor.google.digits')]
        private int $digits,
    ) {
    }

    /**
     * A login that waits for its two-factor code is no Studio user yet. The session route is public, so the
     * two-factor listener does not answer it with its 401, and the UI needs the same step it would get there.
     */
    public function getSessionStatus(): SessionStatus
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof TwoFactorTokenInterface) {
            return new SessionStatus(false, true, TwoFactorStep::fromToken($token)->value, $this->digits);
        }

        return new SessionStatus($this->authorizationChecker->isGranted(UserPermissions::PIMCORE_USER->value));
    }
}
