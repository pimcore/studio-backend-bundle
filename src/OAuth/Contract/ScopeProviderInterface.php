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

/**
 * Contributes OAuth scope identifiers to the authorization server's catalogue.
 *
 * A bundle that exposes its own protected resources implements this and tags the
 * service with {@see ScopeProviderInterface::TAG}. Its scopes are then accepted at
 * the authorization endpoint, registrable by dynamic clients, and advertised in the
 * authorization server metadata, without editing this bundle.
 *
 * Use your own prefix. Sharing another application's scope identifiers makes the
 * consent screen ambiguous and prevents a token being narrowed to one application.
 *
 * Public API.
 */
interface ScopeProviderInterface
{
    public const string TAG = 'pimcore_studio_backend.oauth.scope_provider';

    /**
     * Scope identifiers this provider contributes, e.g. `['datahub:read']`.
     *
     * @return list<string>
     */
    public function scopes(): array;
}
