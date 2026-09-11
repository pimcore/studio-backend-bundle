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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Contract;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResourceMetadata;

/**
 * Registry of OAuth protected resources (audiences).
 *
 * Supports multiple resources; lookups are keyed by the canonical resource URI.
 * Implementations canonicalise on both registration and lookup, so callers may
 * pass any equivalent form of a URI.
 *
 * Public API. Bundles whose endpoints are protected resources register them here,
 * which is what makes their RFC 9728 metadata document resolvable.
 */
interface ResourceRegistryInterface
{
    public function register(ProtectedResource $resource): void;

    public function has(string $canonicalUri): bool;

    public function get(string $canonicalUri): ?ProtectedResource;

    /**
     * @return list<ProtectedResource>
     */
    public function all(): array;

    /**
     * The RFC 9728 Protected Resource Metadata for a registered resource, or
     * null if the URI is not registered.
     */
    public function metadataFor(string $canonicalUri): ?ProtectedResourceMetadata;
}
