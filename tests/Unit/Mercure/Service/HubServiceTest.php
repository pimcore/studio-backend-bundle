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
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\HubService;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UrlServiceInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

final class HubServiceTest extends Unit
{
    private const int CUSTOM_LIFETIME = 900;

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

    private function createHubService(int $cookieLifetime): HubService
    {
        return new HubService(
            $this->makeEmpty(TokenProviderInterface::class, ['getJwt' => 'jwt']),
            $this->makeEmpty(UrlServiceInterface::class, ['getClientSideUrl' => 'https://example.com/hub']),
            $cookieLifetime
        );
    }
}
