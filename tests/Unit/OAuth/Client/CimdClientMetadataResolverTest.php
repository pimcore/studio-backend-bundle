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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Client;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Client\CimdClientMetadataResolver;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use function json_encode;

final class CimdClientMetadataResolverTest extends Unit
{
    /**
     * @param list<MockResponse> $responses
     * @param list<string>       $allowedHosts
     */
    private function resolver(
        array $responses,
        bool $enabled = true,
        bool $allowInsecure = true,
        array $allowedHosts = [],
        ?MockHttpClient $client = null,
    ): CimdClientMetadataResolver {
        return new CimdClientMetadataResolver(
            $client ?? new MockHttpClient($responses),
            new ArrayAdapter(),
            new NullLogger(),
            $enabled,
            $allowedHosts,
            $allowInsecure,
            300,
        );
    }

    private function doc(string $json): MockResponse
    {
        return new MockResponse($json, ['http_code' => 200]);
    }

    public function testResolvesValidDocument(): void
    {
        $url = 'https://app.example/client.json';
        $resolver = $this->resolver([
            $this->doc((string) json_encode([
                'client_name' => 'My App',
                'redirect_uris' => ['https://app.example/cb'],
            ])),
        ]);

        $metadata = $resolver->resolve($url);
        $this->assertNotNull($metadata);
        $this->assertSame($url, $metadata->clientId);
        $this->assertSame('My App', $metadata->name);
        $this->assertSame(['https://app.example/cb'], $metadata->redirectUris);
    }

    public function testDisabledReturnsNull(): void
    {
        $resolver = $this->resolver([$this->doc('{"redirect_uris":["https://a/cb"]}')], enabled: false);
        $this->assertNull($resolver->resolve('https://app.example/client.json'));
    }

    public function testRejectsHttpWhenNotInsecure(): void
    {
        $resolver = $this->resolver([], allowInsecure: false);
        // http is not acceptable in secure mode, so no fetch happens.
        $this->assertNull($resolver->resolve('http://app.example/client.json'));
    }

    public function testRejectsUrlWithFragment(): void
    {
        $resolver = $this->resolver([]);
        $this->assertNull($resolver->resolve('https://app.example/client.json#x'));
    }

    public function testEnforcesHostAllowList(): void
    {
        $resolver = $this->resolver(
            [$this->doc('{"redirect_uris":["https://other.example/cb"]}')],
            allowedHosts: ['app.example'],
        );
        $this->assertNull($resolver->resolve('https://other.example/client.json'));
    }

    public function testRejectsClientIdMismatch(): void
    {
        $url = 'https://app.example/client.json';
        $resolver = $this->resolver([
            $this->doc((string) json_encode([
                'client_id' => 'https://evil.example/client.json',
                'redirect_uris' => ['https://app.example/cb'],
            ])),
        ]);
        $this->assertNull($resolver->resolve($url));
    }

    public function testRejectsMissingRedirectUris(): void
    {
        $resolver = $this->resolver([$this->doc('{"client_name":"No Redirects"}')]);
        $this->assertNull($resolver->resolve('https://app.example/client.json'));
    }

    public function testRejectsNon200(): void
    {
        $resolver = $this->resolver([new MockResponse('not found', ['http_code' => 404])]);
        $this->assertNull($resolver->resolve('https://app.example/client.json'));
    }

    public function testCachesWithinRequest(): void
    {
        $client = new MockHttpClient([$this->doc('{"redirect_uris":["https://app.example/cb"]}')]);
        $resolver = $this->resolver([], client: $client);

        $first = $resolver->resolve('https://app.example/client.json');
        $second = $resolver->resolve('https://app.example/client.json');

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        // The document is fetched once; the second resolve is served from memo/cache.
        $this->assertSame(1, $client->getRequestsCount());
    }
}
