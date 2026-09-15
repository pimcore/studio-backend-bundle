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
 * The authorization server's scope catalogue: the union of the `scopesSupported` of
 * every registered {@see ResourceRegistryInterface} resource.
 *
 * Single source of truth for which scopes exist. The authorization endpoint accepts
 * them, dynamic client registration allows them, and the server metadata advertises
 * them. Declaring a scope on the resource that supports it is the only way to add
 * one: a scope belonging to no resource would be narrowed away at authorization time
 * anyway.
 *
 * Public API.
 */
interface ScopeRegistryInterface
{
    /**
     * All known identifiers, de-duplicated, in resource-registration order.
     *
     * @return list<string>
     */
    public function all(): array;

    public function has(string $scope): bool;
}
