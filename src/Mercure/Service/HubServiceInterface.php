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
    /**
     * The cookie read by hubs speaking the Mercure 1.0 protocol.
     */
    public function createCookie(): Cookie;

    /**
     * One cookie per protocol, carrying the same token: the 1.0 cookie first, then the 0.x one.
     *
     * @return Cookie[]
     */
    public function createCookies(): array;

    /**
     * Lifetime of the cookie returned by createCookie(), in seconds. Clients need it to
     * renew their authorization before the hub stops accepting it.
     */
    public function getCookieLifetime(): int;
}
