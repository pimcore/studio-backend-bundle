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
 * The authorization server's scope catalogue: every identifier contributed by a
 * registered {@see ScopeProviderInterface}.
 *
 * Single source of truth for which scopes exist. The authorization endpoint accepts
 * them, dynamic client registration allows them, and the server metadata advertises
 * them.
 *
 * Public API.
 */
interface ScopeRegistryInterface
{
    /**
     * All known identifiers, de-duplicated, in contribution order.
     *
     * @return list<string>
     */
    public function all(): array;

    public function has(string $scope): bool;
}
