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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ScopeEntity;

/**
 * @internal
 */
final class ScopeRepository implements ScopeRepositoryInterface
{
    public function __construct(
        private readonly ScopeRegistryInterface $scopeRegistry,
    ) {
    }

    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        return $this->scopeRegistry->has($identifier) ? new ScopeEntity($identifier) : null;
    }

    /**
     * @param ScopeEntityInterface[] $scopes
     *
     * @return ScopeEntityInterface[]
     */
    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null,
    ): array {
        // Still a pass-through. The client's registered scopes are now available here, via
        // ClientEntity::getRegisteredScopes(), but intersecting against them is a policy
        // decision rather than plumbing: ClientRegistrar::parseScopes() returns [] when a
        // client registers no `scope` at all, which is the common DCR case, so an
        // intersection has to decide whether [] means "nothing permitted" or "no
        // restriction expressed". Refusing everything would break every client that
        // registers without naming scopes. That decision, and the delegation ceiling
        // (narrowing to what the user may delegate), belong with the scope work.
        return $scopes;
    }
}
