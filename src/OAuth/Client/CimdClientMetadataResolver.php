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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Client;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ClientMetadataResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ClientMetadata;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;
use function array_is_list;
use function array_key_exists;
use function hash;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function parse_url;
use function str_contains;
use function strlen;
use function strtolower;

/**
 * Resolves a URL-form client_id by fetching its Client ID Metadata Document.
 *
 * The client_id is an attacker-influenced URL, so the fetch is guarded: https
 * only (http permitted solely in the dev "allow_insecure" mode), an optional
 * host allow-list, private/loopback network access blocked (unless insecure),
 * no redirects, and a response size/time cap. Results are cached per URL.
 *
 * @internal
 */
final class CimdClientMetadataResolver implements ClientMetadataResolverInterface
{
    private const int MAX_BYTES = 65536;

    private const int TIMEOUT_SECONDS = 5;

    private const int MAX_DURATION_SECONDS = 8;

    /**
     * @var array<string, ?ClientMetadata> in-request memoization
     */
    private array $memo = [];

    /**
     * @param list<string> $allowedHosts
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
        private readonly array $allowedHosts,
        private readonly bool $allowInsecure,
        private readonly int $cacheTtl,
    ) {
    }

    public function resolve(string $clientId): ?ClientMetadata
    {
        if (!$this->enabled) {
            return null;
        }

        if (array_key_exists($clientId, $this->memo)) {
            return $this->memo[$clientId];
        }

        $this->memo[$clientId] = $this->doResolve($clientId);

        return $this->memo[$clientId];
    }

    private function doResolve(string $clientId): ?ClientMetadata
    {
        if (!$this->isAcceptableUrl($clientId)) {
            return null;
        }

        $cacheKey = 'cimd_' . hash('sha256', $clientId);
        $item = $this->cache->getItem($cacheKey);
        if ($item->isHit()) {
            $cached = $item->get();

            return is_array($cached) ? $this->toMetadata($clientId, $cached) : null;
        }

        $data = $this->fetch($clientId);
        if ($data === null) {
            return null;
        }

        $metadata = $this->toMetadata($clientId, $data);
        if ($metadata === null) {
            return null;
        }

        $item->set($data)->expiresAfter($this->cacheTtl);
        $this->cache->save($item);

        return $metadata;
    }

    private function isAcceptableUrl(string $clientId): bool
    {
        if (str_contains($clientId, '#')) {
            return false;
        }

        $parts = parse_url($clientId);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        if ($scheme !== 'https' && !($scheme === 'http' && $this->allowInsecure)) {
            return false;
        }

        if ($this->allowedHosts !== [] && !in_array(strtolower($parts['host']), $this->allowedHosts, true)) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetch(string $url): ?array
    {
        // Block requests to private/loopback ranges to prevent SSRF, except in the
        // explicit dev mode (where the document is typically served locally).
        $client = $this->allowInsecure ? $this->httpClient : new NoPrivateNetworkHttpClient($this->httpClient);

        try {
            $response = $client->request('GET', $url, [
                'timeout' => self::TIMEOUT_SECONDS,
                'max_duration' => self::MAX_DURATION_SECONDS,
                'max_redirects' => 0,
                'headers' => ['Accept' => 'application/json'],
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->warning(
                    'CIMD document fetch returned non-200',
                    ['url' => $url, 'status' => $response->getStatusCode()],
                );

                return null;
            }

            $content = $response->getContent(false);
            if (strlen($content) > self::MAX_BYTES) {
                $this->logger->warning('CIMD document too large', ['url' => $url]);

                return null;
            }

            $data = json_decode($content, true);

            return is_array($data) ? $data : null;
        } catch (Throwable $exception) {
            $this->logger->warning('CIMD document fetch failed', ['url' => $url, 'error' => $exception->getMessage()]);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function toMetadata(string $clientId, array $data): ?ClientMetadata
    {
        // If the document declares a client_id it must equal the URL it was
        // fetched from, so a document cannot impersonate a different client_id.
        if (isset($data['client_id']) && $data['client_id'] !== $clientId) {
            return null;
        }

        $redirectUris = $data['redirect_uris'] ?? null;
        if (!is_array($redirectUris) || $redirectUris === [] || !array_is_list($redirectUris)) {
            return null;
        }

        foreach ($redirectUris as $uri) {
            if (!is_string($uri)) {
                return null;
            }
        }

        $name = is_string($data['client_name'] ?? null) && $data['client_name'] !== ''
            ? $data['client_name']
            : $clientId;

        return new ClientMetadata($clientId, $name, $redirectUris);
    }
}
