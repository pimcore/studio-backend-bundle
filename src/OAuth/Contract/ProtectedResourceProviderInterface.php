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

/**
 * Contributes protected resources (OAuth audiences) to the authorization server.
 *
 * A bundle whose endpoints accept OAuth tokens implements this and tags the service
 * with {@see ProtectedResourceProviderInterface::TAG}. Its resources then appear in
 * {@see ResourceRegistryInterface}, their RFC 9728 metadata documents resolve, the
 * authorization endpoint accepts them as a requested `resource`, and the scopes they
 * declare become part of the server's catalogue.
 *
 * Providers are read lazily: nothing here is called until something actually reads the
 * registry, so a request that touches neither OAuth nor your endpoints pays nothing.
 *
 * Declare resource URIs from configuration, not from the incoming request. Building one
 * from `$request->getSchemeAndHttpHost()` means building it from the `Host` header
 * unless `framework.trusted_hosts` is set, which would let a caller name their own host
 * as a protected resource. Derive from `pimcore_studio_backend.oauth.issuer`, which is
 * required whenever the server is enabled.
 *
 * Returning nothing is normal: a provider whose feature is disabled, or which has no
 * issuer configured to build a URI from, yields an empty iterable.
 *
 * Public API.
 */
interface ProtectedResourceProviderInterface
{
    public const string TAG = 'pimcore_studio_backend.oauth.protected_resource_provider';

    /**
     * The protected resources this bundle exposes.
     *
     * @return iterable<ProtectedResource>
     */
    public function resources(): iterable;
}
