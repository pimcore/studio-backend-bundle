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
use LogicException;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\ProtocolResolver;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UrlServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant\ProtocolVersion;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ProtocolResolverTest extends Unit
{
    private const string HUB_URL = 'http://mercure:3000/.well-known/mercure';

    private const string METADATA_URL = 'http://mercure:3000/.well-known/oauth-protected-resource/.well-known/mercure';

    public function testDetectsA1xHub(): void
    {
        $client = new MockHttpClient(function (string $method, string $url): MockResponse {
            $this->assertSame('GET', $method);
            $this->assertSame(self::METADATA_URL, $url);

            return new MockResponse(
                '{"resource":"http://mercure:3000/.well-known/mercure","mercure_cookie":"mercure_access_token"}',
                ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]
            );
        });

        $this->assertSame(ProtocolVersion::V1, $this->createResolver($client)->getProtocolVersion());
    }

    public function testDetectsA0xHub(): void
    {
        $client = new MockHttpClient(new MockResponse('Not Found', ['http_code' => 404]));

        $this->assertSame(ProtocolVersion::LEGACY, $this->createResolver($client)->getProtocolVersion());
    }

    public function testFallsBackToLegacyWhenTheHubIsUnreachable(): void
    {
        $client = new MockHttpClient(static function (): MockResponse {
            throw new TransportException('Connection refused');
        });

        $this->assertSame(ProtocolVersion::LEGACY, $this->createResolver($client)->getProtocolVersion());
    }

    public function testFallsBackToLegacyOnAServerError(): void
    {
        $client = new MockHttpClient(new MockResponse('Bad Gateway', ['http_code' => 502]));

        $this->assertSame(ProtocolVersion::LEGACY, $this->createResolver($client)->getProtocolVersion());
    }

    public function testFallsBackToLegacyOnANonJsonAnswer(): void
    {
        $client = new MockHttpClient(new MockResponse('<html>proxy</html>', ['http_code' => 200]));

        $this->assertSame(ProtocolVersion::LEGACY, $this->createResolver($client)->getProtocolVersion());
    }

    public function testFallsBackToLegacyWithoutHubUrl(): void
    {
        $client = new MockHttpClient(function (): MockResponse {
            $this->fail('No probe without a hub URL');
        });
        $urlService = $this->makeEmpty(UrlServiceInterface::class, [
            'getServerSideUrl' => static fn () => throw new LogicException('Mercure server URL is not configured.'),
        ]);

        $resolver = new ProtocolResolver($urlService, $client, new ArrayAdapter(), new NullLogger());

        $this->assertSame(ProtocolVersion::LEGACY, $resolver->getProtocolVersion());
    }

    public function testCachesTheCaseWithoutHubUrl(): void
    {
        $urlService = $this->createMock(UrlServiceInterface::class);
        $urlService->method('getServerSideUrl')->willThrowException(new LogicException('not configured'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');
        $logger->expects($this->never())->method('warning');
        $resolver = new ProtocolResolver($urlService, new MockHttpClient(), new ArrayAdapter(), $logger);

        $first = $resolver->getProtocolVersion();
        $second = $resolver->getProtocolVersion();

        $this->assertSame([ProtocolVersion::LEGACY, ProtocolVersion::LEGACY], [$first, $second]);
    }

    public function testLogsAFailureOncePerCachePeriod(): void
    {
        $client = new MockHttpClient([
            new MockResponse('Bad Gateway', ['http_code' => 502]),
            new MockResponse('Bad Gateway', ['http_code' => 502]),
        ]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')
            ->with($this->stringContains(self::METADATA_URL));
        $resolver = new ProtocolResolver($this->createUrlService(), $client, new ArrayAdapter(), $logger);

        $first = $resolver->getProtocolVersion();
        $second = $resolver->getProtocolVersion();

        $this->assertSame([ProtocolVersion::LEGACY, ProtocolVersion::LEGACY], [$first, $second]);
        $this->assertSame(1, $client->getRequestsCount());
    }

    public function testCachesTheDetection(): void
    {
        $client = new MockHttpClient([
            new MockResponse('{"resource":"x"}', ['http_code' => 200]),
            new MockResponse('Not Found', ['http_code' => 404]),
        ]);
        $resolver = $this->createResolver($client, new ArrayAdapter());

        $first = $resolver->getProtocolVersion();
        $second = $resolver->getProtocolVersion();

        $this->assertSame([ProtocolVersion::V1, ProtocolVersion::V1], [$first, $second]);
        $this->assertSame(1, $client->getRequestsCount());
    }

    public function testConfiguredProtocolSkipsDetection(): void
    {
        $client = new MockHttpClient(function (): MockResponse {
            $this->fail('No probe for a configured protocol');
        });

        foreach ([ProtocolVersion::LEGACY, ProtocolVersion::V1] as $version) {
            $resolver = new ProtocolResolver(
                $this->createUrlService(),
                $client,
                new ArrayAdapter(),
                new NullLogger(),
                $version->value
            );

            $this->assertSame($version, $resolver->getProtocolVersion());
        }
    }

    private function createResolver(MockHttpClient $client, ?ArrayAdapter $cache = null): ProtocolResolver
    {
        return new ProtocolResolver($this->createUrlService(), $client, $cache ?? new ArrayAdapter(), new NullLogger());
    }

    private function createUrlService(): UrlServiceInterface
    {
        return $this->makeEmpty(UrlServiceInterface::class, ['getServerSideUrl' => self::HUB_URL]);
    }
}
