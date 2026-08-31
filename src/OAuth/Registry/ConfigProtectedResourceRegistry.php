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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Registry;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ResourceRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResourceMetadata;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;
use function array_values;

/**
 * Config-driven {@see ResourceRegistryInterface}: seeds protected resources
 * from bundle configuration and allows further runtime registration. Supports
 * multiple resources; keyed by canonical URI so lookups are normalisation
 * insensitive.
 *
 * @internal
 */
final class ConfigProtectedResourceRegistry implements ResourceRegistryInterface
{
    /**
     * @var array<string, ProtectedResource> keyed by canonical URI
     */
    private array $resources = [];

    /**
     * @param array<int, array{
     *     uri: string,
     *     scopes_supported?: list<string>,
     *     authorization_servers?: list<string>
     * }> $resources
     */
    public function __construct(array $resources = [])
    {
        foreach ($resources as $resource) {
            $this->register(
                new ProtectedResource(
                    CanonicalUri::canonicalize($resource['uri']),
                    $resource['scopes_supported'] ?? [],
                    $resource['authorization_servers'] ?? [],
                )
            );
        }
    }

    public function register(ProtectedResource $resource): void
    {
        // Canonicalise the resource itself, not only the lookup key: the metadata
        // document echoes `canonicalUri` back as the RFC 9728 `resource` value, which
        // must be the canonical form whatever a caller registered.
        $canonicalUri = CanonicalUri::canonicalize($resource->canonicalUri);

        $this->resources[$canonicalUri] = new ProtectedResource(
            $canonicalUri,
            $resource->scopesSupported,
            $resource->authorizationServers,
        );
    }

    public function has(string $canonicalUri): bool
    {
        return isset($this->resources[CanonicalUri::canonicalize($canonicalUri)]);
    }

    public function get(string $canonicalUri): ?ProtectedResource
    {
        return $this->resources[CanonicalUri::canonicalize($canonicalUri)] ?? null;
    }

    public function all(): array
    {
        return array_values($this->resources);
    }

    public function metadataFor(string $canonicalUri): ?ProtectedResourceMetadata
    {
        $resource = $this->get($canonicalUri);

        return $resource === null ? null : new ProtectedResourceMetadata($resource);
    }
}
