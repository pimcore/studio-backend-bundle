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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service;

use Symfony\Component\HttpFoundation\Cookie;

/**
 * @internal
 */
interface HubServiceInterface
{
    public function createCookie(): Cookie;

    /**
     * One cookie per protocol, carrying the same token: first the 0.x "mercureAuthorization" cookie
     * (the one createCookie() returns), then the 1.0 cookie ("__Secure-mercure_access_token",
     * "mercure_access_token" for a plain HTTP client URL, or the configured cookie_name).
     *
     * @return array{0: Cookie, 1: Cookie}
     */
    public function createCookies(): array;

    /**
     * Lifetime of the cookie returned by createCookie(), in seconds. Clients need it to
     * renew their authorization before the hub stops accepting it.
     */
    public function getCookieLifetime(): int;
}
