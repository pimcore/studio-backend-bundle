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

use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ProtectedResourceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ResourceRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResourceMetadata;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;
use function array_values;

/**
 * {@see ResourceRegistryInterface} over two sources: the resources declared in bundle
 * configuration, and those contributed by tagged
 * {@see ProtectedResourceProviderInterface} services. Keyed by canonical URI, so lookups
 * are normalisation insensitive.
 *
 * Providers are resolved on first read rather than in the constructor. Symfony's tagged
 * iterator is lazy, so a request that never reads the registry never instantiates a
 * provider, let alone asks it for anything.
 *
 * The resolved set is memoised for the life of the instance, which is safe because there
 * is no longer any way to add a resource after construction: providers are services and
 * configuration is fixed at container build, so nothing a request does can change the
 * answer. That was not true while resources were registered from a `kernel.request`
 * listener, and memoising then would have made the answer depend on whether anything
 * happened to ask before the listener ran.
 *
 * @internal
 */
final class ConfigProtectedResourceRegistry implements ResourceRegistryInterface
{
    /**
     * @var array<string, ProtectedResource>|null keyed by canonical URI, null until resolved
     */
    private ?array $resolved = null;

    /**
     * @param array<int, array{
     *     uri: string,
     *     scopes_supported?: list<string>,
     *     authorization_servers?: list<string>
     * }> $resources
     * @param iterable<ProtectedResourceProviderInterface> $providers
     */
    public function __construct(
        private readonly array $resources = [],
        private readonly iterable $providers = [],
    ) {
    }

    public function has(string $canonicalUri): bool
    {
        return isset($this->resolve()[CanonicalUri::canonicalize($canonicalUri)]);
    }

    public function get(string $canonicalUri): ?ProtectedResource
    {
        return $this->resolve()[CanonicalUri::canonicalize($canonicalUri)] ?? null;
    }

    public function all(): array
    {
        return array_values($this->resolve());
    }

    public function metadataFor(string $canonicalUri): ?ProtectedResourceMetadata
    {
        $resource = $this->get($canonicalUri);

        return $resource === null ? null : new ProtectedResourceMetadata($resource);
    }

    /**
     * Providers first, configuration second, so a configured entry for the same canonical
     * URI replaces the contributed one. An operator naming a resource a bundle also
     * provides has made a deliberate choice, usually to narrow its scopes, and must not
     * have it silently overwritten by the default.
     *
     * @return array<string, ProtectedResource>
     */
    private function resolve(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $resolved = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->resources() as $resource) {
                $this->put($resolved, $resource);
            }
        }

        foreach ($this->resources as $resource) {
            $this->put($resolved, new ProtectedResource(
                $resource['uri'],
                $resource['scopes_supported'] ?? [],
                $resource['authorization_servers'] ?? [],
            ));
        }

        $this->resolved = $resolved;

        return $this->resolved;
    }

    /**
     * Canonicalises the resource itself, not only the lookup key: the metadata document
     * echoes `canonicalUri` back as the RFC 9728 `resource` value, which has to be the
     * canonical form whatever a provider or an operator wrote.
     *
     * @param array<string, ProtectedResource> $resolved
     */
    private function put(array &$resolved, ProtectedResource $resource): void
    {
        $canonicalUri = CanonicalUri::canonicalize($resource->canonicalUri);

        $resolved[$canonicalUri] = new ProtectedResource(
            $canonicalUri,
            $resource->scopesSupported,
            $resource->authorizationServers,
        );
    }
}
