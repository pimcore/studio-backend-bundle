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
 * A protected resource (OAuth audience): its canonical URI, the scopes it
 * supports, and the authorization server(s) that may issue tokens for it.
 *
 * `$canonicalUri` need not be pre-canonicalised: the registry canonicalises on
 * registration and on every lookup.
 *
 * Public API: the argument type of {@see \Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ResourceRegistryInterface::register()}.
 */
final readonly class ProtectedResource
{
    /**
     * @param list<string> $scopesSupported
     * @param list<string> $authorizationServers
     */
    public function __construct(
        public string $canonicalUri,
        public array $scopesSupported,
        public array $authorizationServers,
    ) {
    }
}
