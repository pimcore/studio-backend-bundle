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
        private bool $jwtCookieStrictness = true,
        private ?string $jwtCookieHost = null,
        private ?string $cookieName = null,
        private ?AccessTokenFactoryInterface $tokenFactory = null,
    ) {
    }

    public function getCookieLifetime(): int
    {
        return $this->cookieLifetime;
    }

    public function createCookie(): Cookie
    {
        return $this->buildLegacyCookie($this->tokenProvider->getJwt());
    }

    public function createCookies(): array
    {
        $jwt = $this->tokenProvider->getJwt();

        // Each hub reads only the cookie of its own protocol
        return [
            $this->buildLegacyCookie($jwt),
            $this->buildCookie($this->getCookieName($this->isSecure()), $jwt),
        ];
    }

    private function buildLegacyCookie(string $jwt): Cookie
    {
        // 1.x hubs in compatibility mode fall back to this cookie and accept only 0.x tokens in it
        return $this->buildCookie(
            Mercure::AUTHORIZATION_COOKIE_NAME->value,
            $this->tokenFactory?->toLegacy($jwt) ?? $jwt
        );
    }

    private function buildCookie(string $name, string $jwt): Cookie
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
            $name,
            $jwt,
            time() + $this->cookieLifetime,
            $urlParts[Mercure::URL_PATH->value] ?? '/',
            $host,
            $this->isSecure(),
            true,
            false,
            $this->jwtCookieStrictness ? Cookie::SAMESITE_STRICT : Cookie::SAMESITE_NONE
        );
    }

    private function isSecure(): bool
    {
        return parse_url($this->urlService->getClientSideUrl(), PHP_URL_SCHEME) === Mercure::URL_SCHEME_HTTPS->value;
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
