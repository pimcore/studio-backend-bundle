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

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\Request;
use function is_string;
use function sprintf;
use function strlen;

/**
 * Core's session helper accepts a login whose code is still pending, so Studio code that reads
 * the session outside the Studio firewall asks this first. It looks at the serialized token's
 * class only and never unserializes it.
 *
 * @internal
 */
final readonly class PendingSessionChecker implements PendingSessionCheckerInterface
{
    // The firewall context the Studio firewall shares with core's session helper.
    private const string SESSION_KEY = '_security_pimcore_admin';

    public function isCodePending(Request $request): bool
    {
        if (!$request->hasPreviousSession()) {
            return false;
        }

        $serializedToken = $request->getSession()->get(self::SESSION_KEY);
        if (!is_string($serializedToken)) {
            return false;
        }

        return str_starts_with(
            $serializedToken,
            sprintf('O:%d:"%s":', strlen(TwoFactorToken::class), TwoFactorToken::class)
        );
    }
}
