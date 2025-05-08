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

namespace Pimcore\Bundle\StudioBackendBundle\User\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\User\Schema\TwoFactorAuth;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class TwoFactorAuthHydrator implements TwoFactorAuthHydratorInterface
{
    public function hydrate(UserInterface $user): TwoFactorAuth
    {
        $enabled = $user->getTwoFactorAuthentication('enabled');

        return new TwoFactorAuth(
            required: $user->getTwoFactorAuthentication('required'),
            enabled: $enabled,
            type: $user->getTwoFactorAuthentication('type'),
            active: $enabled || $user->getTwoFactorAuthentication('secret'),
        );
    }
}
