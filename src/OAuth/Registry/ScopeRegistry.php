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

use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ResourceRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeRegistryInterface;
use function in_array;

/**
 * The scope catalogue, derived from the protected resources themselves rather than
 * declared a second time alongside them.
 *
 * A scope that no resource supports cannot be used: the authorization request is
 * narrowed to the named resource's `scopesSupported`, so such a scope is either
 * filtered out or refused with `invalid_scope`. Resource definitions therefore
 * already carry every scope that can matter, and deriving the catalogue from them
 * makes the two structurally incapable of disagreeing.
 *
 * @internal
 */
final readonly class ScopeRegistry implements ScopeRegistryInterface
{
    public function __construct(
        private ResourceRegistryInterface $resourceRegistry,
    ) {
    }

    /**
     * Recomputed on every call. The resource registry memoises its own resolution, so
     * this walks an in-memory array of a handful of short strings; keeping a second copy
     * here would buy nothing and add a place for the two to disagree.
     */
    public function all(): array
    {
        $scopes = [];

        foreach ($this->resourceRegistry->all() as $resource) {
            foreach ($resource->scopesSupported as $scope) {
                if ($scope !== '' && !in_array($scope, $scopes, true)) {
                    $scopes[] = $scope;
                }
            }
        }

        return $scopes;
    }

    public function has(string $scope): bool
    {
        return in_array($scope, $this->all(), true);
    }
}
