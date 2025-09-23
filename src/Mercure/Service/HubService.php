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

use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant\Mercure;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

/**
 * @internal
 */
final readonly class HubService implements HubServiceInterface
{
    public function __construct(
        private TokenProviderInterface $tokenProvider,
        private UrlServiceInterface $urlService,
        private int $cookieLifetime = 3600,
        private bool $jwt_cookie_strictness = true,
        private ?string $jwtCookieHost = null,
    ) {
    }

    public function createCookie(): Cookie
    {
        $urlParts = parse_url($this->urlService->getClientSideUrl());

        $host = '';
        if (!empty($this->jwtCookieHost)) {
            $host = $this->jwtCookieHost;
        }

        if ($host === '' && isset($urlParts[Mercure::URL_HOST->value])) {
            $host = $urlParts[Mercure::URL_HOST->value];
        }

        return new Cookie(
            Mercure::AUTHORIZATION_COOKIE_NAME->value,
            $this->tokenProvider->getJwt(),
            time() + $this->cookieLifetime,
            $urlParts[Mercure::URL_PATH->value] ?? '/',
            $host,
            $urlParts[Mercure::URL_SCHEME->value] === Mercure::URL_SCHEME_HTTPS->value,
            true,
            false,
            $this->jwt_cookie_strictness ? Cookie::SAMESITE_STRICT : Cookie::SAMESITE_NONE
        );
    }
}
