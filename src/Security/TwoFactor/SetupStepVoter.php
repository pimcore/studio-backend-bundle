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

use Pimcore\Bundle\StudioBackendBundle\User\Controller\TwoFactor\SetupController;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use function in_array;

/**
 * A login waiting for its first setup has no roles, so the application's access rule for the
 * Studio API (ROLE_PIMCORE_USER) would block the setup endpoint it needs. A rule of its own can't
 * be added from the bundle: Symfony takes access_control from one config source only. This voter
 * grants that one request and abstains from everything else, so it never takes access away.
 *
 * @internal
 */
final readonly class SetupStepVoter implements VoterInterface
{
    private const string ROLE = 'ROLE_PIMCORE_USER';

    public function vote(TokenInterface $token, mixed $subject, array $attributes, mixed $vote = null): int
    {
        if (!in_array(self::ROLE, $attributes, true)
            || !$subject instanceof Request
            || !$token instanceof TwoFactorTokenInterface
            || $token->getCurrentTwoFactorProvider() !== SetupProvider::ALIAS
            || $subject->attributes->get('_route') !== SetupController::ROUTE_NAME
            || !$subject->isMethod(Request::METHOD_POST)
        ) {
            return self::ACCESS_ABSTAIN;
        }

        return self::ACCESS_GRANTED;
    }
}
