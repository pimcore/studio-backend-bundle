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

use Pimcore\Bundle\StudioBackendBundle\Authorization\Controller\TokenLoginController;
use Pimcore\Security\User\User;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface;

/**
 * @internal
 */
final readonly class UserCondition implements TwoFactorConditionInterface
{
    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
    {
        $user = $context->getUser();
        if (!$user instanceof User) {
            return true;
        }

        // Token login skips the code, as in the Classic admin UI.
        if ($context->getRequest()->attributes->get('_route') === TokenLoginController::ROUTE_NAME) {
            return false;
        }

        $pimcoreUser = $user->getUser();

        return $pimcoreUser->getTwoFactorAuthentication('required')
            || $pimcoreUser->getTwoFactorAuthentication('enabled');
    }
}
