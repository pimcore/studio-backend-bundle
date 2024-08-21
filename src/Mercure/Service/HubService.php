<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service;

use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant\Mercure;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Mercure\HubInterface;

/**
 * @internal
 */
final readonly class HubService implements HubServiceInterface
{
    public function __construct(
        private HubInterface $clientHub,
        private int $cookieLifetime = 3600
    ) {
    }

    public function createCookie(): Cookie
    {
        $urlParts = parse_url($this->clientHub->getPublicUrl());

        return new Cookie(
            Mercure::AUTHORIZATION_COOKIE_NAME->value,
            $this->clientHub->getProvider()->getJwt(),
            time() + $this->cookieLifetime,
            $urlParts[Mercure::URL_PATH->value] ?? '/',
            $urlParts[Mercure::URL_HOST->value] ?? '',
            $urlParts[Mercure::URL_SCHEME->value] === Mercure::URL_SCHEME_HTTPS->value,
            true,
            false,
            Cookie::SAMESITE_STRICT
        );
    }
}
