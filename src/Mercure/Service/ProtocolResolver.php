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

use Exception;
use LogicException;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant\Mercure;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant\ProtocolVersion;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function hash;
use function parse_url;
use function sprintf;

/**
 * Detects the hub's protocol from its OAuth protected resource metadata (RFC 9728), which only
 * 1.x hubs serve, so no subscription has to be opened. A 1.x hub in compatibility mode also
 * serves it and accepts the 1.0 parameters.
 *
 * @internal
 */
final readonly class ProtocolResolver implements ProtocolResolverInterface
{
    private const string CACHE_KEY_PREFIX = 'pimcore_studio_backend_mercure_protocol_';

    private const int CACHE_TTL = 600;

    // Short, so a hub that was down is probed again soon
    private const int FAILURE_CACHE_TTL = 60;

    private const int HTTP_STATUS_OK = 200;

    private const int HTTP_STATUS_NOT_FOUND = 404;

    private const float TIMEOUT_SECONDS = 2.0;

    public function __construct(
        private UrlServiceInterface $urlService,
        private HttpClientInterface $httpClient,
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger,
        private string $protocolVersion = ProtocolVersion::AUTO->value,
    ) {
    }

    public function getProtocolVersion(): ProtocolVersion
    {
        $configured = ProtocolVersion::tryFrom($this->protocolVersion) ?? ProtocolVersion::AUTO;
        if ($configured !== ProtocolVersion::AUTO) {
            return $configured;
        }

        try {
            $hubUrl = $this->urlService->getServerSideUrl();
        } catch (LogicException) {
            $hubUrl = null;
        }

        // Every outcome is cached, so a failure is logged once per cache period, not on every authorization
        $item = $this->cache->getItem(self::CACHE_KEY_PREFIX . hash('xxh128', $hubUrl ?? ''));
        if ($item->isHit()) {
            $cached = ProtocolVersion::tryFrom((string)$item->get());
            if ($cached !== null) {
                return $cached;
            }
        }

        [$detected, $ttl] = $this->detect($hubUrl);
        $this->cache->save($item->set($detected->value)->expiresAfter($ttl));

        return $detected;
    }

    /**
     * @return array{0: ProtocolVersion, 1: int}
     */
    private function detect(?string $hubUrl): array
    {
        if ($hubUrl === null) {
            // Nothing is published without hub_url_server, so there is no hub to ask
            $this->logger->info(sprintf(
                'Mercure protocol detection skipped without hub_url_server, using %s',
                ProtocolVersion::LEGACY->value
            ));

            return [ProtocolVersion::LEGACY, self::CACHE_TTL];
        }

        $metadataUrl = $hubUrl;

        try {
            $metadataUrl = $this->getMetadataUrl($hubUrl);
            $response = $this->httpClient->request('GET', $metadataUrl, [
                'timeout' => self::TIMEOUT_SECONDS,
                'max_duration' => self::TIMEOUT_SECONDS,
                'headers' => ['Accept' => 'application/json'],
            ]);
            $status = $response->getStatusCode();

            if ($status === self::HTTP_STATUS_OK) {
                // Throws unless the body is the JSON metadata document, e.g. for a proxy's catch-all page
                $response->toArray(false);

                return [ProtocolVersion::V1, self::CACHE_TTL];
            }

            if ($status === self::HTTP_STATUS_NOT_FOUND) {
                return [ProtocolVersion::LEGACY, self::CACHE_TTL];
            }

            $reason = sprintf('HTTP status %d', $status);
        } catch (Exception $exception) {
            $reason = $exception->getMessage();
        }

        $this->logger->warning(sprintf(
            'Mercure protocol detection at %s failed, using %s: %s',
            $metadataUrl,
            ProtocolVersion::LEGACY->value,
            $reason
        ));

        return [ProtocolVersion::LEGACY, self::FAILURE_CACHE_TTL];
    }

    /**
     * RFC 9728 inserts the well-known path between the hub's origin and its path.
     *
     * @throws LogicException
     */
    private function getMetadataUrl(string $hubUrl): string
    {
        $parts = parse_url($hubUrl);
        if (!isset($parts[Mercure::URL_SCHEME->value], $parts[Mercure::URL_HOST->value])) {
            throw new LogicException(sprintf('Invalid Mercure hub URL "%s".', $hubUrl));
        }

        return $parts[Mercure::URL_SCHEME->value] . '://' . $parts[Mercure::URL_HOST->value] .
            (isset($parts['port']) ? ':' . $parts['port'] : '') .
            Mercure::PROTECTED_RESOURCE_PATH->value .
            ($parts[Mercure::URL_PATH->value] ?? '');
    }
}
