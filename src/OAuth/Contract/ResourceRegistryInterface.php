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
 * Read model of the OAuth protected resources (audiences) this installation exposes.
 *
 * Lookups are keyed by the canonical resource URI, and implementations canonicalise on
 * lookup, so callers may pass any equivalent form of a URI.
 *
 * Read-only by design. A bundle contributes resources by implementing
 * {@see ProtectedResourceProviderInterface}, not by mutating this: the set of valid
 * audiences is a property of the configuration, and a request that could add to it is a
 * request that could name its own audience.
 *
 * Public API.
 */
interface ResourceRegistryInterface
{
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
