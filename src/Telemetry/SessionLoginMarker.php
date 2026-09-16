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

namespace Pimcore\Bundle\StudioBackendBundle\Telemetry;

use Symfony\Component\HttpFoundation\Request;
use function is_int;
use function time;

/**
 * Login marker kept in the Symfony session of the stateful Studio firewall. Written by
 * `LoginTelemetrySubscriber`, read by `LogoutTelemetrySubscriber` before Symfony invalidates the
 * session on logout.
 *
 * @internal
 */
final readonly class SessionLoginMarker implements LoginMarkerInterface
{
    public function record(Request $request): void
    {
        if (!$request->hasSession()) {
            return;
        }

        $request->getSession()->set(self::SESSION_KEY, time());
    }

    public function sessionDurationSeconds(Request $request): ?int
    {
        if (!$request->hasSession()) {
            return null;
        }

        $loginAt = $request->getSession()->get(self::SESSION_KEY);
        if (!is_int($loginAt)) {
            return null;
        }

        $duration = time() - $loginAt;

        // A login in the future is a clock or tampering artefact, not a duration.
        return $duration >= 0 ? $duration : null;
    }
}
