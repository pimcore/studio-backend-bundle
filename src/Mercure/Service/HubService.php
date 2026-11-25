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
use function array_map;

/**
 * @internal
 */
final readonly class HubService implements HubServiceInterface
{
    public function __construct(
        private TokenProviderInterface $tokenProvider,
        private UrlServiceInterface $urlService,
        private int $cookieLifetime = 3600,
        private bool $jwtCookieStrictness = true,
        private ?string $jwtCookieHost = null,
        private ?string $cookieName = null,
    ) {
    }

    public function getCookieLifetime(): int
    {
        return $this->cookieLifetime;
    }

    public function createCookie(): Cookie
    {
        return $this->createCookies()[0];
    }

    public function createCookies(): array
    {
        $urlParts = parse_url($this->urlService->getClientSideUrl());

        $host = '';
        if (!empty($this->jwtCookieHost)) {
            $host = $this->jwtCookieHost;
        }

        if ($host === '' && isset($urlParts[Mercure::URL_HOST->value])) {
            $host = $urlParts[Mercure::URL_HOST->value];
        }

        $secure = ($urlParts[Mercure::URL_SCHEME->value] ?? null) === Mercure::URL_SCHEME_HTTPS->value;
        $jwt = $this->tokenProvider->getJwt();

        // Each hub reads only the cookie of its own protocol
        return array_map(
            fn (string $name): Cookie => new Cookie(
                $name,
                $jwt,
                time() + $this->cookieLifetime,
                $urlParts[Mercure::URL_PATH->value] ?? '/',
                $host,
                $secure,
                true,
                false,
                $this->jwtCookieStrictness ? Cookie::SAMESITE_STRICT : Cookie::SAMESITE_NONE
            ),
            [$this->getCookieName($secure), Mercure::AUTHORIZATION_COOKIE_NAME->value]
        );
    }

    private function getCookieName(bool $secure): string
    {
        if (!empty($this->cookieName)) {
            return $this->cookieName;
        }

        // Browsers drop __Secure- cookies that are not sent over HTTPS
        return $secure ? Mercure::SECURE_COOKIE_NAME->value : Mercure::COOKIE_NAME->value;
    }
}
