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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Dto;

/**
 * RFC 9728 Protected Resource Metadata document for a single resource.
 *
 * Served at the well-known protected-resource endpoint and referenced from the
 * `WWW-Authenticate` challenge on the MCP endpoint. Bearer tokens are accepted
 * in the header only.
 *
 * Public API: the return type of {@see \Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ResourceRegistryInterface::metadataFor()}.
 */
final readonly class ProtectedResourceMetadata
{
    public function __construct(
        private ProtectedResource $resource,
    ) {
    }

    /**
     * @return array{
     *     resource: string,
     *     authorization_servers: list<string>,
     *     scopes_supported: list<string>,
     *     bearer_methods_supported: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'resource' => $this->resource->canonicalUri,
            'authorization_servers' => $this->resource->authorizationServers,
            'scopes_supported' => $this->resource->scopesSupported,
            'bearer_methods_supported' => ['header'],
        ];
    }
}
