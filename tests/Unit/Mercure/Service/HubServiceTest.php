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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mercure\Service;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Model\TopicCollection;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\AccessTokenFactory;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\AudienceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\ClientTokenService;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\HubService;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader\TopicLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UrlServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use function array_slice;

final class HubServiceTest extends Unit
{
    private const int CUSTOM_LIFETIME = 900;

    private const string JWT_KEY = 'a-test-secret-that-is-long-enough-for-hmac-sha256';

    public function testGetCookieLifetimeReturnsConfiguredValue(): void
    {
        $this->assertSame(
            self::CUSTOM_LIFETIME,
            $this->createHubService(self::CUSTOM_LIFETIME)->getCookieLifetime()
        );
    }

    public function testGetCookieLifetimeDefaultsToOneHour(): void
    {
        $service = new HubService(
            $this->makeEmpty(TokenProviderInterface::class, ['getJwt' => 'jwt']),
            $this->makeEmpty(UrlServiceInterface::class, ['getClientSideUrl' => 'https://example.com/hub']),
        );

        $this->assertSame(3600, $service->getCookieLifetime());
    }

    /**
     * The lifetime a client renews on and the lifetime the cookie actually expires on must be the
     * same number. If they drift apart, a client that renews "in time" still reconnects with an
     * expired cookie, which the hub accepts as anonymous, silently dropping every private update.
     */
    public function testCookieExpiryMatchesTheAdvertisedLifetime(): void
    {
        $service = $this->createHubService(self::CUSTOM_LIFETIME);

        $before = time();
        $expiresAt = $service->createCookie()->getExpiresTime();
        $after = time();

        $this->assertGreaterThanOrEqual($before + $service->getCookieLifetime(), $expiresAt);
        $this->assertLessThanOrEqual($after + $service->getCookieLifetime(), $expiresAt);
    }

    /**
     * The cookie carries a JWT with its own `exp`, and the hub rejects the subscription as soon as
     * that claim has passed - regardless of how long the browser keeps sending the cookie. So the
     * advertised lifetime has to match the TOKEN, not just the outer cookie: `LcobucciFactory`
     * otherwise derives `exp` from `session.cookie_lifetime` (or 3600), and a `cookie_lifetime`
     * configured above that would leave the client renewing long after the hub stopped accepting
     * its token.
     */
    public function testAdvertisedLifetimeMatchesTheTokenExpiry(): void
    {
        $lifetime = 7200;
        $service = new HubService(
            new ClientTokenService(
                $this->makeEmpty(TopicLoaderInterface::class, [
                    'loadTopics' => new TopicCollection([], [], [], ['studio-backend-default']),
                ]),
                new AccessTokenFactory(self::JWT_KEY, 'https://example.com'),
                $this->makeEmpty(AudienceServiceInterface::class, [
                    'getSubscriberAudience' => ['http://mercure/.well-known/mercure'],
                ]),
                $this->makeEmpty(SecurityServiceInterface::class, ['isLoggedIn' => false]),
                $lifetime
            ),
            $this->makeEmpty(UrlServiceInterface::class, ['getClientSideUrl' => 'https://example.com/hub']),
            $lifetime
        );

        $cookie = $service->createCookie();
        $claims = $this->decodeClaims((string) $cookie->getValue());

        $this->assertSame($lifetime, $service->getCookieLifetime());
        $this->assertEqualsWithDelta($lifetime, $claims['exp'] - time(), 5);
        $this->assertEqualsWithDelta($claims['exp'], $cookie->getExpiresTime(), 5);
    }

    public function testCookieNameIsSecurePrefixedOverHttps(): void
    {
        $cookie = $this->createHubService(self::CUSTOM_LIFETIME)->createCookies()[1];

        $this->assertSame('__Secure-mercure_access_token', $cookie->getName());
        $this->assertTrue($cookie->isSecure());
    }

    /**
     * Browsers drop __Secure- cookies that are not sent over HTTPS.
     */
    public function testCookieNameHasNoPrefixOverHttp(): void
    {
        $service = new HubService(
            $this->makeEmpty(TokenProviderInterface::class, ['getJwt' => 'jwt']),
            $this->makeEmpty(UrlServiceInterface::class, ['getClientSideUrl' => 'http://localhost/hub']),
        );

        $cookie = $service->createCookies()[1];

        $this->assertSame('mercure_access_token', $cookie->getName());
        $this->assertFalse($cookie->isSecure());
    }

    /**
     * Each hub reads the cookie of its own protocol; both carry the same token and scope.
     */
    public function testCookiesServeBothProtocols(): void
    {
        $service = new HubService(
            $this->makeEmpty(TokenProviderInterface::class, ['getJwt' => 'jwt']),
            $this->makeEmpty(UrlServiceInterface::class, ['getClientSideUrl' => 'https://example.com/hub']),
            jwtCookieHost: '.example.com'
        );

        $cookies = $service->createCookies();

        $this->assertSame(
            ['mercureAuthorization', '__Secure-mercure_access_token'],
            array_map(static fn (Cookie $cookie): string => $cookie->getName(), $cookies)
        );
        foreach ($cookies as $cookie) {
            $this->assertSame('jwt', $cookie->getValue());
            $this->assertSame('/hub', $cookie->getPath());
            $this->assertSame('.example.com', $cookie->getDomain());
            $this->assertTrue($cookie->isSecure());
            $this->assertTrue($cookie->isHttpOnly());
        }
    }

    public function testCookiesShareOneToken(): void
    {
        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $tokenProvider->expects($this->once())->method('getJwt')->willReturn('jwt');
        $service = new HubService(
            $tokenProvider,
            $this->makeEmpty(UrlServiceInterface::class, ['getClientSideUrl' => 'http://localhost/hub']),
        );

        $this->assertSame(
            ['mercureAuthorization', 'mercure_access_token'],
            array_map(static fn (Cookie $cookie): string => $cookie->getName(), $service->createCookies())
        );
    }

    /**
     * Callers outside this bundle (e.g. pimcore/portal-engine) set createCookie() with a token that
     * only 0.x hubs and 1.x hubs in compatibility mode accept, so it must stay the 0.x cookie.
     */
    public function testCreateCookieKeepsTheLegacyCookie(): void
    {
        foreach (['https://example.com/hub' => true, 'http://localhost/hub' => false] as $url => $secure) {
            $service = new HubService(
                $this->makeEmpty(TokenProviderInterface::class, ['getJwt' => 'jwt']),
                $this->makeEmpty(UrlServiceInterface::class, ['getClientSideUrl' => $url]),
                cookieName: 'custom_mercure_cookie'
            );

            $cookie = $service->createCookie();

            $this->assertSame('mercureAuthorization', $cookie->getName());
            $this->assertSame('jwt', $cookie->getValue());
            $this->assertSame('/hub', $cookie->getPath());
            $this->assertSame($secure, $cookie->isSecure());
        }
    }

    /**
     * 1.x hubs in compatibility mode fall back to the legacy cookie when theirs is missing,
     * e.g. over plain HTTP without cookie_name, and accept only a 0.x token there.
     */
    public function testLegacyCookieCarriesA0xToken(): void
    {
        $tokenFactory = new AccessTokenFactory(self::JWT_KEY, 'https://example.com');
        $service = new HubService(
            new ClientTokenService(
                $this->makeEmpty(TopicLoaderInterface::class, [
                    'loadTopics' => new TopicCollection([], [], [], ['studio-backend-default/user/2']),
                ]),
                $tokenFactory,
                $this->makeEmpty(AudienceServiceInterface::class, [
                    'getSubscriberAudience' => ['http://localhost/.well-known/mercure'],
                ]),
                $this->makeEmpty(SecurityServiceInterface::class, ['isLoggedIn' => false])
            ),
            $this->makeEmpty(UrlServiceInterface::class, ['getClientSideUrl' => 'http://localhost/hub']),
            tokenFactory: $tokenFactory
        );

        [$legacyCookie, $cookie] = $service->createCookies();
        [$legacyHeader, $legacyClaims] = $this->decode((string) $legacyCookie->getValue());
        [$header, $claims] = $this->decode((string) $cookie->getValue());

        $this->assertSame('JWT', $legacyHeader['typ']);
        $this->assertSame([
            'mercure' => ['subscribe' => ['studio-backend-default/user/2']],
            'iss' => 'https://example.com',
            'exp' => $claims['exp'],
        ], $legacyClaims);
        $this->assertSame('at+jwt', $header['typ']);
        $this->assertArrayHasKey('authorization_details', $claims);
        $this->assertSame($legacyClaims['mercure'], $claims['mercure']);
        $this->assertSame('JWT', $this->decode((string) $service->createCookie()->getValue())[0]['typ']);
    }

    public function testLegacyTokenIsOnlyMintedFromOwnTokens(): void
    {
        $tokenFactory = new AccessTokenFactory(self::JWT_KEY, 'https://example.com');
        $foreign = (new AccessTokenFactory(self::JWT_KEY . '-other', 'https://example.com'))
            ->create(['topic'], [], 'sub', ['aud'], new DateTimeImmutable('+1 hour'));

        $this->assertSame('jwt', $tokenFactory->toLegacy('jwt'));
        $this->assertSame($foreign, $tokenFactory->toLegacy($foreign));
    }

    public function testConfiguredCookieNameIsUsed(): void
    {
        $service = new HubService(
            $this->makeEmpty(TokenProviderInterface::class, ['getJwt' => 'jwt']),
            $this->makeEmpty(UrlServiceInterface::class, ['getClientSideUrl' => 'https://example.com/hub']),
            cookieName: 'custom_mercure_cookie'
        );

        $this->assertSame('custom_mercure_cookie', $service->createCookies()[1]->getName());
        $this->assertSame('mercureAuthorization', $service->createCookie()->getName());
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeClaims(string $jwt): array
    {
        return $this->decode($jwt)[1];
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function decode(string $jwt): array
    {
        return array_map(
            static fn (string $part): array => json_decode(
                base64_decode(strtr($part, '-_', '+/')),
                true,
                512,
                JSON_THROW_ON_ERROR
            ),
            array_slice(explode('.', $jwt), 0, 2)
        );
    }

    private function createHubService(int $cookieLifetime): HubService
    {
        return new HubService(
            $this->makeEmpty(TokenProviderInterface::class, ['getJwt' => 'jwt']),
            $this->makeEmpty(UrlServiceInterface::class, ['getClientSideUrl' => 'https://example.com/hub']),
            $cookieLifetime
        );
    }
}
