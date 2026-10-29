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

namespace Pimcore\Bundle\StudioBackendBundle\User\TwoFactor;

use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;

/**
 * Hands a secret that is not saved yet to scheb, without putting it on the user object.
 *
 * @internal
 */
final readonly class PendingSecret implements TwoFactorInterface
{
    public function __construct(
        private string $username,
        private string $secret,
    ) {
    }

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return true;
    }

    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->username;
    }

    public function getGoogleAuthenticatorSecret(): string
    {
        return $this->secret;
    }
}
