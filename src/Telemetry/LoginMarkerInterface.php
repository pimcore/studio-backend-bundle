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

/**
 * Remembers when the current Studio session logged in, so the logout telemetry can report how long the
 * session lasted. The marker is a plain timestamp in the session - never who logged in.
 *
 * @internal
 */
interface LoginMarkerInterface
{
    /**
     * Session attribute holding the login time as a unix timestamp.
     */
    public const string SESSION_KEY = '_pimcore_studio_telemetry_login_at';

    /**
     * Stores the current time as the session's login time; a request without a session is left alone.
     */
    public function record(Request $request): void;

    /**
     * Seconds since the recorded login, or null when the session carries no usable marker.
     */
    public function sessionDurationSeconds(Request $request): ?int;
}
