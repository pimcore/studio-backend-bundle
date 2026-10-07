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
use Pimcore\Bundle\StudioBackendBundle\Mercure\Model\TopicCollection;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\ClientTokenService;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\HubService;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader\TopicLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UrlServiceInterface;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

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
                new LcobucciFactory(self::JWT_KEY),
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

    /**
     * @return array<string, mixed>
     */
    private function decodeClaims(string $jwt): array
    {
        $payload = explode('.', $jwt)[1];

        return json_decode(base64_decode(strtr($payload, '-_', '+/')), true, 512, JSON_THROW_ON_ERROR);
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
